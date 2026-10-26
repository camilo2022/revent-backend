<?php

namespace App\Services\Siigo;

use Illuminate\Support\Collection;

/**
 * Arma el cuerpo (payload) que el monolito de Siigo espera para crear una
 * orden de compra, a partir de los detalles ya validados del Excel.
 */
class SiigoPurchaseOrderPayload
{
    public function build(array $config, array $user, object $purchase_order_type, array $rete_iva, array $rete_ica, array $provider, array $cost_center, string $doc_date, Collection $detalles, array $warehouse, string $tipo)
    {
        $total_base = (float) $this->bcSumMoney($detalles, 'BaseValue');
        $vat_total_value = (float) $this->bcSumMoney($detalles, 'TaxAdd_Value');
        $tax_disc_total_value = (float) $this->bcSumMoney($detalles, 'TaxDiscount_Value');

        $erp_document_total = collect();

        $detalles->filter(function ($detalle) {
                return isset($detalle['TaxAdd_Id'])
                    && (int) $detalle['TaxAdd_Id'] > 0
                    && (float) ($detalle['TaxAdd_Value'] ?? 0) > 0;
            })
            ->groupBy('TaxAdd_Id')
            ->each(function ($items, $tax_id) use ($erp_document_total) {
                $erp_document_total->push([
                    'Id' => (int) $tax_id,
                    'Value' => (float) $this->bcSumMoney($items, 'TaxAdd_Value'),
                    'TotalBase' => (float) $this->bcSumMoney($items, 'BaseValue'),
                ]);
            });

        $detalles->filter(function ($detalle) {
                return isset($detalle['TaxDiscount_Id'])
                    && (int) $detalle['TaxDiscount_Id'] > 0
                    && (float) ($detalle['TaxDiscount_Value'] ?? 0) > 0;
            })
            ->groupBy('TaxDiscount_Id')
            ->each(function ($items, $tax_id) use ($erp_document_total) {

                $erp_document_total->push([
                    'Id' => (int) $tax_id,
                    'Value' => (float) $this->bcSumMoney($items, 'TaxDiscount_Value'),
                    'TotalBase' => (float) $this->bcSumMoney($items, 'BaseValue'),
                ]);
            });

        $erp_document_total = $erp_document_total->values()->all();

        $ret_vat_total_code = (int) ($config['rete_iva'] ?? -1);
        $ret_vat_total_value = 0;
        $ret_vat_total_percentage = -1;
        $ret_vat_name = '';
        $ret_vat_base_value = 0;

        if ($purchase_order_type->IsReteIva && $ret_vat_total_code != -1) {
            $rete_iva_selected = collect($rete_iva)->firstWhere('Id', $ret_vat_total_code);
            if ($rete_iva_selected) {
                $ret_vat_total_percentage = (float) $rete_iva_selected['Value'];
                $ret_vat_name = $rete_iva_selected['Name'];
                $ret_vat_base_value = $vat_total_value;
                $ret_vat_total_value = round($ret_vat_base_value * ($ret_vat_total_percentage / 100), 2);
            }
        }

        $ret_ica_total_code = (int) ($config['rete_ica'] ?? -1);
        $ret_ica_total_value = 0;
        $ret_ica_total_percentage = -1;
        $ret_ica_name = '';
        $ret_ica_base_value = 0;

        if ($purchase_order_type->IsReteIca && $ret_ica_total_code != -1) {
            $rete_ica_selected = collect($rete_ica)->firstWhere('Id', $ret_ica_total_code);
            if ($rete_ica_selected) {
                $ret_ica_total_percentage = (float) $rete_ica_selected['Value'];
                $ret_ica_name = $rete_ica_selected['Name'];
                $ret_ica_base_value = $total_base;
                $ret_ica_total_value = round($ret_ica_base_value * ($ret_ica_total_percentage / 1000), 2);
            }
        }

        $total_value = (float) bcsub(bcsub(bcadd(number_format($total_base, 2, '.', ''), number_format($vat_total_value, 2, '.', ''), 2), number_format($tax_disc_total_value, 2, '.', ''),2), bcadd(number_format($ret_ica_total_value, 2, '.', ''), number_format($ret_vat_total_value, 2, '.', ''), 2), 2);
        $total_value = round($total_base + $vat_total_value - $tax_disc_total_value - $ret_ica_total_value - $ret_vat_total_value, 2);

        $observaciones = "DIRIGIDO A: {$warehouse['id']} - {$warehouse['name']}. TIPO: {$tipo}. FECHA LIMITE: {$config['fecha_limite']}. " . ($config['observaciones'] ?? '');

        return [
            "Process" => 1,
            "ERPDocType" => $purchase_order_type->ERPType,
            "ERPDocGeneral" => [
                "ERPDocumentID" => -1,
                "ForeignMoneyCode" => "",
                "ApplyForAllDay" => false,
                "GeneratePdf" => false,
                "lngLastAccount" => -1,
                "WFInstanceID" => -1,
                "ERPDocDate" => $doc_date,
                "Number" => -1,
                "AccountID" => $provider['ID'],
                "ContactID" => $provider['contactID'],
                "SalesmanID" => $user['id'],
                "ERPDocumentType" => $purchase_order_type->ERPDocumentType,
                "ERPDocClass" => $purchase_order_type->ERPDocClass,
                "ERPDocCode" => $purchase_order_type->ERPDocCode,
                "ConsumptionTaxTotalValue" => 0,
                "TaxDiscTotalValue" => $tax_disc_total_value,
                "VATTotalValue" => $vat_total_value,
                "TotalValue" => $total_value,
                "TotalBase" => $total_base,
                "TotalBaseAIU" => 0,
                "RetICAName" => $ret_ica_name,
                "RetICABaseValue" => $ret_ica_base_value,
                "RetICATotalCode" => $ret_ica_total_code,
                "RetICATotalValue" => $ret_ica_total_value,
                "RetICATotaPercentage" => $ret_ica_total_percentage,
                "RetVATTotalCode" => $ret_vat_total_code,
                "RetVATTotalValue" => $ret_vat_total_value,
                "RetVATTotalPercentage" => $ret_vat_total_percentage,
                "RetVATName" => $ret_vat_name,
                "RetVATBaseValue" => $ret_vat_base_value,
                "AttachmentsFSItemsGUID" => $purchase_order_type->AttachmentsFSItemsGUID,
                "TotalDiscountPercentage" => 0,
                "TotalDiscountValue" => 0,
                "Header" => $purchase_order_type->Header,
                "CommercialConditions" => $purchase_order_type->CommercialCoditions,
                "ERPDocumentCode" => -1,
                "Observations" => $observaciones,
                "IsAllowDecimals" => $purchase_order_type->AllowDecimals,
                "CostCenterCode" => $purchase_order_type->UseCostCenter ? $cost_center['id'] : $purchase_order_type->CostCenterDefaultCode,
                "ConfigInitial" => json_encode(["BaseAIU" => "False"]),
                "ConsumptionValue" => 0,
                "AllowTaxImpoByValue" => $purchase_order_type->AllowTaxImpoByValue,
                "TaxIncluded" => $purchase_order_type->TaxIncluded,
                "ExternalERPDocName" => null,
                "ERPDocType" => [
                    "UseDIANVadility" => $purchase_order_type->UseDIANVadility,
                    "ERPDocumentType" => $purchase_order_type->ERPDocumentType,
                    "ERPType" => $purchase_order_type->ERPType,
                    "IsPercentage" => $purchase_order_type->IsPercentage,
                    "IsReteIva" => $purchase_order_type->IsReteIva,
                    "IsReteIca" => $purchase_order_type->IsReteIca,
                    "Atachments" => $purchase_order_type->Atachments,
                    "IsSalesBySalesman" => $purchase_order_type->IsSalesBySalesman,
                    "CostCenterDefaultName" => $purchase_order_type->CostCenterDefaultName,
                    "UseWarehouse" => $purchase_order_type->UseWarehouse,
                    "LastWarehouseCode" => $purchase_order_type->LastWarehouseCode,
                    "UseUnitValue" => $purchase_order_type->UseUnitValue,
                    "EnableTwoTaxes" => $purchase_order_type->EnableTwoTaxes,
                    "ShowAIU" => $purchase_order_type->ShowAIU,
                    "UseQuantity" => $purchase_order_type->UseQuantity,
                    "IsItemsByAccount" => $purchase_order_type->IsItemsByAccount,
                    "AllowTaxImpoByValue" => $purchase_order_type->AllowTaxImpoByValue,
                    "TaxIncluded" => $purchase_order_type->TaxIncluded,
                    "CreatedAsProviderByItem" => $purchase_order_type->CreatedAsProviderByItem,
                    "AllowValueWithChargeTaxes" => $purchase_order_type->AllowValueWithChargeTaxes,
                    "NextNumber" => $purchase_order_type->NextNumber,
                    "AllowTaxAdd2" => $purchase_order_type->AllowTaxAdd2,
                    "TaxAdd2Name" => $purchase_order_type->TaxAdd2Name,
                    "ERPDocumentTypeID" => $purchase_order_type->ERPDocumentTypeID,
                    "Name" => $purchase_order_type->Name,
                    "ERPDocClass" => $purchase_order_type->ERPDocClass,
                    "ERPDocCode" => $purchase_order_type->ERPDocCode,
                    "NumberInitial" => $purchase_order_type->NumberInitial,
                    "DIANPrefix" => $purchase_order_type->DIANPrefix,
                    "ERPDocument" => $purchase_order_type->ERPDocument,
                    "DIANResolution" => $purchase_order_type->DIANResolution,
                    "DIANAuthorizationDate" => $purchase_order_type->DIANAuthorizationDate,
                    "DIANNumberStart" => $purchase_order_type->DIANNumberStart,
                    "DIANNumberEnd" => $purchase_order_type->DIANNumberEnd,
                    "DIANVadility" => $purchase_order_type->DIANVadility,
                    "SubjectEMail" => $purchase_order_type->SubjectEMail,
                    "BodyEmail" => $purchase_order_type->BodyEmail,
                    "IsAutomaticSignature" => $purchase_order_type->IsAutomaticSignature,
                    "CreatedByDate" => $purchase_order_type->CreatedByDate,
                    "CreatedByUser" => $purchase_order_type->CreatedByUser,
                    "UpdatedByUser" => $purchase_order_type->UpdatedByUser,
                    "UpdatedByDate" => $purchase_order_type->UpdatedByDate,
                    "InternalDescription" => $purchase_order_type->InternalDescription,
                    "IsAutomaticEnum" => $purchase_order_type->IsAutomaticEnum,
                    "Consecutive" => $purchase_order_type->Consecutive,
                    "IsOnlinePayment" => $purchase_order_type->IsOnlinePayment,
                    "IsRoundNumber" => $purchase_order_type->IsRoundNumber,
                    "IsDiscountPercentaje" => $purchase_order_type->IsDiscountPercentaje,
                    "IsDiscountValue" => $purchase_order_type->IsDiscountValue,
                    "Comments" => $purchase_order_type->Comments,
                    "IsNew" => $purchase_order_type->IsNew,
                    "UseDocumentSupport" => $purchase_order_type->UseDocumentSupport,
                    "Prefix" => $purchase_order_type->Prefix,
                    "PrintMsg" => $purchase_order_type->PrintMsg,
                    "CreateTask" => $purchase_order_type->CreateTask,
                    "TemplateName" => $purchase_order_type->TemplateName,
                    "TemplatePath" => $purchase_order_type->TemplatePath,
                    "ViewSelectedColumns" => $purchase_order_type->ViewSelectedColumns,
                    "LayoutSelectedColumns" => $purchase_order_type->LayoutSelectedColumns,
                    "SignatureEmail" => $purchase_order_type->SignatureEmail,
                    "AllowRetVAT" => $purchase_order_type->AllowRetVAT,
                    "AllowRetICA" => $purchase_order_type->AllowRetICA,
                    "IsActive" => $purchase_order_type->IsActive,
                    "Header" => $purchase_order_type->Header,
                    "CommercialCoditions" => $purchase_order_type->CommercialCoditions,
                    "TopContent" => $purchase_order_type->TopContent,
                    "BottomContent" => $purchase_order_type->BottomContent,
                    "LeftContent" => $purchase_order_type->LeftContent,
                    "RightContent" => $purchase_order_type->RightContent,
                    "FormatName" => $purchase_order_type->FormatName,
                    "AttachmentsFSItemsGUID" => $purchase_order_type->AttachmentsFSItemsGUID,
                    "AllowDecimals" => $purchase_order_type->AllowDecimals,
                    "AllowSalesBySalesman" => $purchase_order_type->AllowSalesBySalesman,
                    "AllowSalesByAccount" => $purchase_order_type->AllowSalesByAccount,
                    "AllowOrderReference" => $purchase_order_type->AllowOrderReference,
                    "AllowOrderDelivery" => $purchase_order_type->AllowOrderDelivery,
                    "UseCostCenter" => $purchase_order_type->UseCostCenter,
                    "CostCenterMandatory" => $purchase_order_type->CostCenterMandatory,
                    "CostCenterDefaultCode" => $purchase_order_type->CostCenterDefaultCode,
                    "IsShowResolutionDIAN" => $purchase_order_type->IsShowResolutionDIAN,
                    "CLDocumentTypeCode" => $purchase_order_type->CLDocumentTypeCode,
                    "TaxImpoByValue" => $purchase_order_type->TaxImpoByValue,
                    "AssociatedType" => $purchase_order_type->AssociatedType,
                    "Address" => $purchase_order_type->Address,
                    "CityCode" => $purchase_order_type->CityCode,
                    "Phone" => $purchase_order_type->Phone,
                    "AccountCode" => $purchase_order_type->AccountCode,
                    "ContactCode" => $purchase_order_type->ContactCode,
                    "ProductWarehouseCode" => $purchase_order_type->ProductWarehouseCode,
                    "PriceListCode" => $purchase_order_type->PriceListCode,
                    "AllowCards" => $purchase_order_type->AllowCards,
                    "AllowOthers" => $purchase_order_type->AllowOthers,
                    "AllowCredit" => $purchase_order_type->AllowCredit,
                    "AllowChangeSalesman" => $purchase_order_type->AllowChangeSalesman,
                    "AllowDiscount" => $purchase_order_type->AllowDiscount,
                    "AllowAllPriceList" => $purchase_order_type->AllowAllPriceList,
                    "AllowChangePrice" => $purchase_order_type->AllowChangePrice,
                    "AllowPreliminary" => $purchase_order_type->AllowPreliminary,
                    "AllowAIU" => $purchase_order_type->AllowAIU,
                    "TaxAdd2Code" => $purchase_order_type->TaxAdd2Code,
                    "AllowAdvance" => $purchase_order_type->AllowAdvance,
                    "ACAccountCodeAdvance" => $purchase_order_type->ACAccountCodeAdvance,
                    "ACAccountCode" => $purchase_order_type->ACAccountCode,
                    "ApplyAccountingBook" => $purchase_order_type->ApplyAccountingBook,
                    "ElectronicInvoiceType" => $purchase_order_type->ElectronicInvoiceType,
                    "ElectronicInvoiceKey" => $purchase_order_type->ElectronicInvoiceKey,
                    "CreditACPaymentMeanCode" => $purchase_order_type->CreditACPaymentMeanCode,
                    "ERPDocumentTypeJournalEntryCode" => $purchase_order_type->ERPDocumentTypeJournalEntryCode,
                    "MoneyIncomeACAccountCode" => $purchase_order_type->MoneyIncomeACAccountCode,
                    "MoneyWithdrawalACAccountCode" => $purchase_order_type->MoneyWithdrawalACAccountCode,
                    "ESiigoTestEntryType" => $purchase_order_type->ESiigoTestEntryType,
                    "ESiigoTestIncludeCufeQR" => $purchase_order_type->ESiigoTestIncludeCufeQR,
                    "AllowReprintInvoices" => $purchase_order_type->AllowReprintInvoices,
                    "ShowValues" => $purchase_order_type->ShowValues,
                    "UseCodeBarReader" => $purchase_order_type->UseCodeBarReader,
                    "DIANEndDate" => $purchase_order_type->DIANEndDate,
                    "AllowSelfWithholdingTax" => $purchase_order_type->AllowSelfWithholdingTax,
                    "SelfWithholdingTaxCode" => $purchase_order_type->SelfWithholdingTaxCode,
                    "SelfWithholdingLowerLimit" => $purchase_order_type->SelfWithholdingLowerLimit,
                    "AllowMultipleRemittance" => $purchase_order_type->AllowMultipleRemittance,
                    "AllowDocumentReference" => $purchase_order_type->AllowDocumentReference,
                    "Establishment" => $purchase_order_type->Establishment,
                    "EmissionPoint" => $purchase_order_type->EmissionPoint,
                    "ACAccountCodeGift" => $purchase_order_type->ACAccountCodeGift,
                    "TypeRetention" => $purchase_order_type->TypeRetention,
                    "ERPDocumentTypeCreditNoteCode" => $purchase_order_type->ERPDocumentTypeCreditNoteCode,
                    "CanAnnullInvoice" => $purchase_order_type->CanAnnullInvoice,
                    "CanMakeCreditNote" => $purchase_order_type->CanMakeCreditNote,
                    "ERPDocumentAllowDecimals" => $purchase_order_type->ERPDocumentAllowDecimals,
                    "NotifyWhenNotHaveStock" => $purchase_order_type->NotifyWhenNotHaveStock,
                    "AllowISR" => $purchase_order_type->AllowISR,
                    "NExterior" => $purchase_order_type->NExterior,
                    "NInside" => $purchase_order_type->NInside,
                    "Suburb" => $purchase_order_type->Suburb,
                    "Location" => $purchase_order_type->Location,
                    "PostalCode" => $purchase_order_type->PostalCode,
                    "UtilityACAccountCode" => $purchase_order_type->UtilityACAccountCode,
                    "LossACAccountCode" => $purchase_order_type->LossACAccountCode,
                    "DiscountBonusACAccountCode" => $purchase_order_type->DiscountBonusACAccountCode,
                    "CanChangeWarehouse" => $purchase_order_type->CanChangeWarehouse,
                    "TaxClassificationCode" => $purchase_order_type->TaxClassificationCode,
                    "EnableAutomaticCash" => $purchase_order_type->EnableAutomaticCash,
                    "AutomaticCashACAccountCode" => $purchase_order_type->AutomaticCashACAccountCode,
                    "AutomaticCashACPaymentMeanCode" => $purchase_order_type->AutomaticCashACPaymentMeanCode,
                    "ComplementaryDataList" => $purchase_order_type->ComplementaryDataList,
                    "IsDiscountPercent" => $purchase_order_type->IsDiscountPercentaje,
                ],
                "ExternalPrefix" => null,
                "ExternalConsecutive" => null,
                "ExchangePersonalized" => false,
                "CreatedAsProviderByItem" => $purchase_order_type->CreatedAsProviderByItem,
                "IsDiscountPercent" => $purchase_order_type->IsDiscountPercentaje,
            ],
            "lstERPDocItem" => $detalles->toArray(),
            "ERPDocumentTotal" => $erp_document_total,
            "ERPDocumentDue" => null,
            "ERPDocumentPayment" => null,
            "AdvanceValue" => "",
            "PaymentTotal" => "",
            "ERPDocumentConfigModel" => null,
            "JSONERPDocType" => [
                "ERPDocumentTypeID" => $purchase_order_type->ERPDocumentTypeID,
                "Name" => $purchase_order_type->Name,
                "ERPDocClass" => $purchase_order_type->ERPDocClass,
                "ERPDocCode" => $purchase_order_type->ERPDocCode,
                "IsAutomaticEnum" => $purchase_order_type->IsAutomaticEnum,
                "IsDiscountPercentaje" => $purchase_order_type->IsDiscountPercentaje,
                "BaseAIU" => false,
                "TemplateName" => $purchase_order_type->TemplateName,
                "InternalDescription" => $purchase_order_type->InternalDescription,
                "AllowRetVAT" => $purchase_order_type->AllowRetVAT,
                "AllowRetICA" => $purchase_order_type->AllowRetICA,
                "AllowDecimals" => $purchase_order_type->AllowDecimals,
                "AllowSalesBySalesman" => $purchase_order_type->AllowSalesBySalesman,
                "UseCostCenter" => $purchase_order_type->UseCostCenter,
                "IsShowResolutionDIAN" => $purchase_order_type->IsShowResolutionDIAN,
                "ACAccountCode" => $purchase_order_type->ACAccountCode,
                "EnableTwoTaxesOnSave" => false,
                "AllowTaxAdd2" => $purchase_order_type->AllowTaxAdd2,
                "IsConsumptionAddValue" => true,
                "TaxIncluded" => $purchase_order_type->TaxIncluded,
                "TaxImpoByValue" => $purchase_order_type->TaxImpoByValue,
                "CreatedAsProviderByItem" => $purchase_order_type->CreatedAsProviderByItem,
                "ExternalERPDocName" => null,
            ],
        ];
    }

    private function bcSumMoney(Collection $items, string $field): string
    {
        return $items->reduce(function (string $carry, $item) use ($field) {
            $value = number_format((float) ($item[$field] ?? 0), 2, '.', '');
            return bcadd($carry, $value, 2);
        }, '0.00');
    }
}
