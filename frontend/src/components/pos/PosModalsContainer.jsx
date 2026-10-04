import React, { Suspense, lazy } from 'react';
import {
  TerminalSelectModal,
  OpenShiftModal,
  CloseShiftModal,
  CashMovementModal,
  PrinterSettingsModal,
  RecallModal,
  MemberSelectModal,
  RedeemPointModal,
  ChangeModal,
  BankSelectModal,
  MultiBankSelectModal,
  DiscountModal,
  VoucherModal,
  OpenPriceModal,
  MultiPaymentModal,
  DirectCashModal,
  DirectCardModal,
  MultiCashModal,
  MultiCardModal,
  QtyModal,
  ReprintOldModal,
  DigitalProductModal
} from './modals';
import { AuthorizationModal } from '../AuthorizationModal';
import { ReturnItemModal } from '../ReturnItemModal';
import { ReceiptPreview } from '../ReceiptPreview';
import { EODReportPreview } from '../EODReportPreview';

// Lazy load large PPOB dashboard modal (592 lines)
const PpobMenuModal = lazy(() => import('./modals/PpobMenuModal').then(m => ({ default: m.PpobMenuModal })));

export const PosModalsContainer = ({
  // Terminal Select
  isTerminalModalOpen,
  allTerminals,
  handleSelectTerminal,

  // Shifts
  isOpenShiftModalOpen,
  selectedShiftName,
  setSelectedShiftName,
  startingCash,
  setStartingCash,
  handleOpenShift,
  isCloseShiftModalOpen,
  setIsCloseShiftModalOpen,
  activeShift,
  actualCash,
  setActualCash,
  handleCloseShift,
  isProcessing,

  // Cash Movement
  isCashMovementModalOpen,
  setIsCashMovementModalOpen,
  cashMovementType,
  setCashMovementType,
  cashMovementAmount,
  setCashMovementAmount,
  cashMovementDesc,
  setCashMovementDesc,
  handleCashMovement,

  // Printer Settings
  isPrinterSettingsOpen,
  setIsPrinterSettingsOpen,
  localPrinterSettings,
  setLocalPrinterSettings,

  // EOD
  eodReportData,
  setEodReportData,
  branchSettings,
  onLogout,

  // Recall / Hold
  isRecallModalOpen,
  setIsRecallModalOpen,
  heldTransactions,
  setHeldTransactions,
  handleRecallTransaction,
  safeSetItem,

  // Member
  isMemberModalOpen,
  setIsMemberModalOpen,
  customers,
  setSelectedCustomer,
  memberSearchQuery,
  setMemberSearchQuery,

  // Points
  isRedeemPointModalOpen,
  setIsRedeemPointModalOpen,
  pointRedemptionEnabled,
  pointRedemptionValue,
  minimumPointsToRedeem,
  selectedCustomer,
  finalAmount,
  payments,
  setPayments,
  pointsToRedeemInput,
  setPointsToRedeemInput,
  handleApplyPoints,

  // Change & Receipt
  changeModalInfo,
  setChangeModalInfo,
  showReceiptPreview,
  setShowReceiptPreview,
  lastTransaction,

  // Bank
  isBankSelectOpen,
  setIsBankSelectOpen,
  banks,
  setSelectedBank,
  setDirectCardInput,
  setIsDirectCardAmountModalOpen,
  isMultiBankSelectOpen,
  setIsMultiBankSelectOpen,
  setIsMultiPaymentModalOpen,
  pendingCardAmount,
  setPendingCardAmount,

  // Auth & Return
  pendingAuthAction,
  setPendingAuthAction,
  authToken,
  isOnline,
  isReturnModalOpen,
  setIsReturnModalOpen,
  handleReturnSuccess,

  // Discount
  discountModal,
  setDiscountModal,
  discountInputVal,
  setDiscountInputVal,
  applyEnteredDiscount,

  // Voucher
  isVoucherModalOpen,
  setIsVoucherModalOpen,
  voucherInput,
  setVoucherInput,
  voucherSource,
  setVoucherSource,

  // Open Price
  isOpenPriceModalOpen,
  setIsOpenPriceModalOpen,
  openPriceTargetItem,
  setOpenPriceTargetItem,
  newOpenPrice,
  setNewOpenPrice,
  items,
  setItems,

  // Payments
  isMultiPaymentModalOpen,
  processTransaction,
  isDirectCashModalOpen,
  setIsDirectCashModalOpen,
  directCashInput,
  setDirectCashInput,
  isDirectCardAmountModalOpen,
  directCardInput,
  selectedBank,
  isMultiCashModalOpen,
  setIsMultiCashModalOpen,
  multiCashInput,
  setMultiCashInput,
  isMultiCardAmountModalOpen,
  setIsMultiCardAmountModalOpen,
  multiCardInput,
  setMultiCardInput,

  // Qty & Reprint Old
  isQtyModalOpen,
  setIsQtyModalOpen,
  nextItemQty,
  setNextItemQty,
  isReprintOldModalOpen,
  setIsReprintOldModalOpen,
  oldReceiptInput,
  setOldReceiptInput,
  handleReprintOld,

  // PPOB
  isPpobMenuOpen,
  setIsPpobMenuOpen,
  ppobSearchQuery,
  setPpobSearchQuery,
  fetchPpobTransactions,
  isFetchingPpobTransactions,
  ppobTransactions,
  handleCheckPpobStatus,
  handleReprintPpob,

  // Digital Product
  isDigitalInputModalOpen,
  setIsDigitalInputModalOpen,
  pendingDigitalProduct,
  setPendingDigitalProduct,
  customerNoInput,
  setCustomerNoInput,
  handleDigitalProductSubmit,

  // Helper utils & refs
  formatCurrency,
  formatThousandSeparator,
  setAlertMsg,
  barcodeInput
}) => {
  return (
    <>
      {/* Terminal Selection Modal */}
      {isTerminalModalOpen && (
        <TerminalSelectModal
          isOpen={isTerminalModalOpen}
          allTerminals={allTerminals}
          onSelectTerminal={handleSelectTerminal}
        />
      )}

      {/* Buka Shift Modal */}
      {isOpenShiftModalOpen && (
        <OpenShiftModal
          isOpen={isOpenShiftModalOpen}
          selectedShiftName={selectedShiftName}
          setSelectedShiftName={setSelectedShiftName}
          startingCash={startingCash}
          setStartingCash={setStartingCash}
          formatThousandSeparator={formatThousandSeparator}
          onOpenShift={handleOpenShift}
        />
      )}

      {/* Tutup Shift Modal */}
      {isCloseShiftModalOpen && (
        <CloseShiftModal
          isOpen={isCloseShiftModalOpen}
          onClose={() => setIsCloseShiftModalOpen(false)}
          activeShift={activeShift}
          actualCash={actualCash}
          setActualCash={setActualCash}
          formatThousandSeparator={formatThousandSeparator}
          formatCurrency={formatCurrency}
          onCloseShift={handleCloseShift}
          isProcessing={isProcessing}
        />
      )}

      {/* Cash Movement (Kas Masuk/Keluar) Modal */}
      {isCashMovementModalOpen && (
        <CashMovementModal
          isOpen={isCashMovementModalOpen}
          onClose={() => setIsCashMovementModalOpen(false)}
          cashMovementType={cashMovementType}
          setCashMovementType={setCashMovementType}
          cashMovementAmount={cashMovementAmount}
          setCashMovementAmount={setCashMovementAmount}
          cashMovementDesc={cashMovementDesc}
          setCashMovementDesc={setCashMovementDesc}
          onSave={handleCashMovement}
          isProcessing={isProcessing}
        />
      )}

      {/* Printer Settings Modal */}
      {isPrinterSettingsOpen && (
        <PrinterSettingsModal
          isOpen={isPrinterSettingsOpen}
          onClose={() => setIsPrinterSettingsOpen(false)}
          localPrinterSettings={localPrinterSettings}
          setLocalPrinterSettings={setLocalPrinterSettings}
        />
      )}

      {/* EOD Report Preview */}
      {eodReportData && (
        <EODReportPreview
          eodData={eodReportData}
          branchSettings={branchSettings}
          onPrint={() => {}}
          onClose={() => {
            setEodReportData(null);
            onLogout();
          }}
        />
      )}

      {/* Recall (HOLD) Modal */}
      {isRecallModalOpen && (
        <RecallModal
          isOpen={isRecallModalOpen}
          onClose={() => setIsRecallModalOpen(false)}
          heldTransactions={heldTransactions}
          setHeldTransactions={setHeldTransactions}
          onRecallTransaction={handleRecallTransaction}
          formatCurrency={formatCurrency}
          safeSetItem={safeSetItem}
        />
      )}

      {/* Member Selection Modal */}
      {isMemberModalOpen && (
        <MemberSelectModal
          isOpen={isMemberModalOpen}
          onClose={() => setIsMemberModalOpen(false)}
          customers={customers}
          setSelectedCustomer={setSelectedCustomer}
          memberSearchQuery={memberSearchQuery}
          setMemberSearchQuery={setMemberSearchQuery}
        />
      )}

      {/* Point Redemption Modal */}
      {isRedeemPointModalOpen && (
        <RedeemPointModal
          isOpen={isRedeemPointModalOpen}
          onClose={() => setIsRedeemPointModalOpen(false)}
          pointRedemptionEnabled={pointRedemptionEnabled}
          formatCurrency={formatCurrency}
          pointRedemptionValue={pointRedemptionValue}
          minimumPointsToRedeem={minimumPointsToRedeem}
          selectedCustomer={selectedCustomer}
          finalAmount={finalAmount}
          payments={payments}
          pointsToRedeemInput={pointsToRedeemInput}
          setPointsToRedeemInput={setPointsToRedeemInput}
          handleApplyPoints={handleApplyPoints}
        />
      )}

      {/* Change Modal Overlay */}
      {changeModalInfo && (
        <ChangeModal
          changeModalInfo={changeModalInfo}
          onClose={() => setChangeModalInfo(null)}
          onPrintReceipt={() => {
            setChangeModalInfo(null);
            setShowReceiptPreview(true);
          }}
          formatCurrency={formatCurrency}
          autoPrint={localPrinterSettings.autoPrint}
        />
      )}

      {/* Receipt Preview */}
      {showReceiptPreview && lastTransaction && (
        <ReceiptPreview
          transaction={lastTransaction}
          branchSettings={branchSettings}
          autoPrintSettings={localPrinterSettings}
          onPrint={() => {
            window.print();
            setShowReceiptPreview(false);
          }}
          onClose={() => setShowReceiptPreview(false)}
        />
      )}

      {/* Bank Selection Modal */}
      {isBankSelectOpen && (
        <BankSelectModal
          isOpen={isBankSelectOpen}
          onClose={() => setIsBankSelectOpen(false)}
          banks={banks}
          formatCurrency={formatCurrency}
          onSelectBank={(bank) => {
            setSelectedBank(bank);
            const sisa = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
            setDirectCardInput(sisa !== 0 ? formatThousandSeparator(sisa) : '');
            setIsBankSelectOpen(false);
            setIsDirectCardAmountModalOpen(true);
          }}
        />
      )}

      {/* Multi Payment Bank Selection Modal */}
      {isMultiBankSelectOpen && (
        <MultiBankSelectModal
          isOpen={isMultiBankSelectOpen}
          onClose={() => {
            setIsMultiBankSelectOpen(false);
            setIsMultiPaymentModalOpen(true);
          }}
          banks={banks}
          pendingCardAmount={pendingCardAmount}
          formatCurrency={formatCurrency}
          onSelectBank={(bank, minReq, isBelowMin) => {
            if (isBelowMin) {
              setAlertMsg({
                text: `Nominal porsi pembayaran ${bank.name} minimal ${formatCurrency(minReq)}! (Diinput: ${formatCurrency(pendingCardAmount)})`,
                type: 'error'
              });
              setTimeout(() => setAlertMsg(null), 4000);
              return;
            }
            setPayments([
              ...payments,
              {
                method: 'CARD',
                amount: pendingCardAmount,
                bankId: bank.id,
                label: `${bank.type === 'QRIS' ? 'QRIS' : 'Card'}: ${bank.name}`
              }
            ]);
            setIsMultiBankSelectOpen(false);
            setIsMultiPaymentModalOpen(true);
          }}
        />
      )}

      {/* Authorization Modal */}
      {pendingAuthAction && (
        <AuthorizationModal
          actionName={pendingAuthAction.name}
          authToken={authToken}
          isOnline={isOnline}
          onSuccess={(user) => {
            setPendingAuthAction(null);
            pendingAuthAction.callback();
          }}
          onCancel={() => setPendingAuthAction(null)}
        />
      )}

      {/* Return Item Modal */}
      {isReturnModalOpen && (
        <ReturnItemModal
          authToken={authToken}
          onSuccess={handleReturnSuccess}
          onCancel={() => setIsReturnModalOpen(false)}
        />
      )}

      {/* Discount Modal */}
      {discountModal && (
        <DiscountModal
          discountModal={discountModal}
          discountInputVal={discountInputVal}
          setDiscountInputVal={setDiscountInputVal}
          formatThousandSeparator={formatThousandSeparator}
          onApply={applyEnteredDiscount}
          onCancel={() => {
            setDiscountModal(null);
            setDiscountInputVal('');
            barcodeInput.current?.focus();
          }}
        />
      )}

      {/* Voucher Modal */}
      {isVoucherModalOpen && (
        <VoucherModal
          isOpen={isVoucherModalOpen}
          voucherInput={voucherInput}
          setVoucherInput={setVoucherInput}
          onProcessVoucher={async () => {
            try {
              const res = await fetch(`/api/v1/vouchers/validate?code=${voucherInput}`, {
                headers: { Authorization: `Bearer ${authToken}` }
              });
              const data = await res.json();
              if (!res.ok || !data.valid) {
                setAlertMsg({ text: data.message || 'Voucher tidak valid', type: 'error' });
                return;
              }

              setPayments((prev) => [
                ...prev,
                {
                  method: 'VOUCHER',
                  amount: parseFloat(data.voucher.nominal_value),
                  voucherId: data.voucher.id,
                  label: `Voucher: ${data.voucher.code}`
                }
              ]);
              setAlertMsg({
                text: `Voucher Rp ${formatCurrency(data.voucher.nominal_value)} ditambahkan!`,
                type: 'success'
              });
              setIsVoucherModalOpen(false);
              setVoucherInput('');
              if (voucherSource === 'MULTI') {
                setIsMultiPaymentModalOpen(true);
                setVoucherSource(null);
              } else {
                setTimeout(() => barcodeInput.current?.focus(), 100);
              }
            } catch (err) {
              setAlertMsg({ text: 'Gagal memvalidasi voucher (offline/error)', type: 'error' });
            }
          }}
          onCancel={() => {
            setIsVoucherModalOpen(false);
            setVoucherInput('');
            if (voucherSource === 'MULTI') {
              setIsMultiPaymentModalOpen(true);
              setVoucherSource(null);
            } else {
              barcodeInput.current?.focus();
            }
          }}
        />
      )}

      {/* Open Price Modal */}
      {isOpenPriceModalOpen && (
        <OpenPriceModal
          isOpen={isOpenPriceModalOpen}
          openPriceTargetItem={openPriceTargetItem}
          newOpenPrice={newOpenPrice}
          setNewOpenPrice={setNewOpenPrice}
          formatCurrency={formatCurrency}
          onSubmit={() => {
            const val = parseFloat(newOpenPrice);
            if (!isNaN(val) && val >= 0) {
              setItems(
                items.map((i) =>
                  i.productId === openPriceTargetItem.productId
                    ? { ...i, unitPrice: val, originalUnitPrice: i.originalUnitPrice || i.unitPrice }
                    : i
                )
              );
              setAlertMsg({ text: 'Harga berhasil diubah', type: 'success' });
              setIsOpenPriceModalOpen(false);
              setNewOpenPrice('');
              setOpenPriceTargetItem(null);
              setTimeout(() => barcodeInput.current?.focus(), 100);
            }
          }}
          onCancel={() => {
            setIsOpenPriceModalOpen(false);
            setNewOpenPrice('');
            setOpenPriceTargetItem(null);
            setTimeout(() => barcodeInput.current?.focus(), 100);
          }}
        />
      )}

      {/* Multi Payment Modal */}
      {isMultiPaymentModalOpen && (
        <MultiPaymentModal
          isOpen={isMultiPaymentModalOpen}
          onClose={() => {
            setIsMultiPaymentModalOpen(false);
            barcodeInput.current?.focus();
          }}
          finalAmount={finalAmount}
          payments={payments}
          setPayments={setPayments}
          formatCurrency={formatCurrency}
          onAddVoucher={() => {
            setVoucherSource('MULTI');
            setIsVoucherModalOpen(true);
            setIsMultiPaymentModalOpen(false);
          }}
          onAddCash={() => {
            const sisa = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
            setMultiCashInput(sisa > 0 ? formatThousandSeparator(sisa) : '');
            setIsMultiCashModalOpen(true);
            setIsMultiPaymentModalOpen(false);
          }}
          onAddCard={() => {
            const sisa = finalAmount - payments.reduce((sum, p) => sum + p.amount, 0);
            if (sisa > 0) {
              setMultiCardInput(formatThousandSeparator(sisa));
              setIsMultiCardAmountModalOpen(true);
              setIsMultiPaymentModalOpen(false);
            }
          }}
          onProcessPay={() => {
            setIsMultiPaymentModalOpen(false);
            processTransaction('MULTI');
          }}
        />
      )}

      {/* Direct Cash Modal */}
      {isDirectCashModalOpen && (
        <DirectCashModal
          isOpen={isDirectCashModalOpen}
          onClose={() => {
            setIsDirectCashModalOpen(false);
            setDirectCashInput('');
          }}
          finalAmount={finalAmount}
          payments={payments}
          directCashInput={directCashInput}
          setDirectCashInput={setDirectCashInput}
          formatCurrency={formatCurrency}
          formatThousandSeparator={formatThousandSeparator}
          onPay={(val) => {
            setIsDirectCashModalOpen(false);
            processTransaction('CASH', null, val);
          }}
        />
      )}

      {/* Direct Card Amount Modal */}
      {isDirectCardAmountModalOpen && (
        <DirectCardModal
          isOpen={isDirectCardAmountModalOpen}
          onClose={() => {
            setIsDirectCardAmountModalOpen(false);
            setDirectCardInput('');
          }}
          selectedBank={selectedBank}
          finalAmount={finalAmount}
          payments={payments}
          directCardInput={directCardInput}
          setDirectCardInput={setDirectCardInput}
          formatCurrency={formatCurrency}
          formatThousandSeparator={formatThousandSeparator}
          onPay={(val, minReq, inputVal) => {
            if (val === null) {
              setAlertMsg({
                text: `Nominal pembayaran via ${selectedBank?.name || 'Bank'} minimal ${formatCurrency(minReq)}! (Diinput: ${formatCurrency(inputVal)})`,
                type: 'error'
              });
              setTimeout(() => setAlertMsg(null), 4000);
              return;
            }
            setIsDirectCardAmountModalOpen(false);
            processTransaction('CARD', selectedBank?.id, val);
          }}
        />
      )}

      {/* Multi Cash Modal */}
      {isMultiCashModalOpen && (
        <MultiCashModal
          isOpen={isMultiCashModalOpen}
          onClose={() => {
            setIsMultiCashModalOpen(false);
            setMultiCashInput('');
            setIsMultiPaymentModalOpen(true);
          }}
          finalAmount={finalAmount}
          payments={payments}
          multiCashInput={multiCashInput}
          setMultiCashInput={setMultiCashInput}
          formatCurrency={formatCurrency}
          formatThousandSeparator={formatThousandSeparator}
          onAddCash={(val) => {
            setPayments([...payments, { method: 'CASH', amount: val, label: 'Tunai' }]);
            setIsMultiCashModalOpen(false);
            setMultiCashInput('');
            setIsMultiPaymentModalOpen(true);
          }}
        />
      )}

      {/* Multi Card Amount Modal */}
      {isMultiCardAmountModalOpen && (
        <MultiCardModal
          isOpen={isMultiCardAmountModalOpen}
          onClose={() => {
            setIsMultiCardAmountModalOpen(false);
            setMultiCardInput('');
            setIsMultiPaymentModalOpen(true);
          }}
          finalAmount={finalAmount}
          payments={payments}
          multiCardInput={multiCardInput}
          setMultiCardInput={setMultiCardInput}
          formatCurrency={formatCurrency}
          formatThousandSeparator={formatThousandSeparator}
          onNext={(val) => {
            setPendingCardAmount(val);
            setIsMultiCardAmountModalOpen(false);
            setMultiCardInput('');
            setIsMultiBankSelectOpen(true);
          }}
        />
      )}

      {/* Qty Modal */}
      {isQtyModalOpen && (
        <QtyModal
          isOpen={isQtyModalOpen}
          nextItemQty={nextItemQty}
          setNextItemQty={setNextItemQty}
          onConfirm={() => {
            setIsQtyModalOpen(false);
            setTimeout(() => barcodeInput.current?.focus(), 100);
          }}
          onCancel={() => {
            setNextItemQty('');
            setIsQtyModalOpen(false);
            barcodeInput.current?.focus();
          }}
        />
      )}

      {/* Reprint Old Receipt Modal */}
      {isReprintOldModalOpen && (
        <ReprintOldModal
          isOpen={isReprintOldModalOpen}
          oldReceiptInput={oldReceiptInput}
          setOldReceiptInput={setOldReceiptInput}
          onSubmit={handleReprintOld}
          onCancel={() => {
            setIsReprintOldModalOpen(false);
            setOldReceiptInput('');
            setTimeout(() => barcodeInput.current?.focus(), 100);
          }}
        />
      )}

      {/* PPOB Menu Modal (Lazy Loaded) */}
      {isPpobMenuOpen && (
        <Suspense fallback={null}>
          <PpobMenuModal
            isOpen={isPpobMenuOpen}
            onClose={() => setIsPpobMenuOpen(false)}
            ppobSearchQuery={ppobSearchQuery}
            setPpobSearchQuery={setPpobSearchQuery}
            fetchPpobTransactions={fetchPpobTransactions}
            isFetchingPpobTransactions={isFetchingPpobTransactions}
            ppobTransactions={ppobTransactions}
            handleCheckPpobStatus={handleCheckPpobStatus}
            handleReprintPpob={handleReprintPpob}
          />
        </Suspense>
      )}

      {/* Digital Product Modal */}
      {isDigitalInputModalOpen && (
        <DigitalProductModal
          isOpen={isDigitalInputModalOpen}
          onClose={() => {
            setIsDigitalInputModalOpen(false);
            setPendingDigitalProduct(null);
          }}
          pendingDigitalProduct={pendingDigitalProduct}
          customerNoInput={customerNoInput}
          setCustomerNoInput={setCustomerNoInput}
          onSubmit={handleDigitalProductSubmit}
        />
      )}
    </>
  );
};

export default PosModalsContainer;
