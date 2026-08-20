(() => {
  "use strict";

  const form = document.querySelector("#loan-create-form");

  if (!form) {
    return;
  }

  const loanType = form.querySelector("#loan-type");
  const collateral = form.querySelector("#loan-collateral");
  const principal = form.querySelector("#principal-amount");
  const interestRate = form.querySelector("#interest-rate");
  const amortizationType = form.querySelector("#amortization-type");
  const paymentFrequency = form.querySelector("#payment-frequency");
  const manualPayment = form.querySelector("#manual-payment");
  const terms = form.querySelector("#terms-months");
  const startDate = form.querySelector("#start-date");

  const standardAmortization = form.querySelector("[data-standard-amortization]");
  const paymentFrequencyGroup = form.querySelector("[data-payment-frequency]");
  const manualPaymentGroup = form.querySelector("[data-manual-payment]");
  const realPropertyGroup = form.querySelector("[data-real-property]");

  const processingFee = form.querySelector("[data-processing-fee]");
  const insurance = form.querySelector("[data-insurance]");
  const netProceeds = form.querySelector("[data-net-proceeds]");
  const calculationNote = form.querySelector("[data-calculation-note]");
  const validationBox = form.querySelector("[data-loan-validation]");
  const reviewButton = form.querySelector("[data-review-loan]");

  let interestRateDirty = false;

  const money = (value) =>
    Number.isFinite(value) ? value.toLocaleString("en-PH", {
      style: "currency",
      currency: "PHP",
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }) : "₱0.00";

  const numericValue = (input) => {
    if (!input) {
      return 0;
    }

    return Number(input.value.replace(/,/g, "")) || 0;
  };

  const setHidden = (element, hidden) => {
    if (!element) {
      return;
    }

    element.hidden = hidden;

    element.querySelectorAll("input, select, textarea").forEach((input) => {
      input.disabled = hidden;

      if (hidden) {
        input.required = false;
      }
    });
  };

  const updateInterestRate = () => {
    if (!loanType || !interestRate || interestRateDirty) {
      return;
    }

    interestRate.value = loanType.value === "Micro-Finance Loan" ? "5" : "2";
  };

  const updateConditionalFields = () => {
    const isMicroFinance = loanType?.value === "Micro-Finance Loan";
    const isManual = amortizationType?.value === "Manual";
    const isRealProperty = collateral?.value === "Real Property";

    setHidden(standardAmortization, isMicroFinance);
    setHidden(paymentFrequencyGroup, !isMicroFinance);
    setHidden(manualPaymentGroup, isMicroFinance || !isManual);
    setHidden(realPropertyGroup, !isRealProperty);

    if (amortizationType) {
      amortizationType.required = !isMicroFinance;
      if (isMicroFinance) {
        amortizationType.value = "";
      }
    }

    if (paymentFrequency) {
      paymentFrequency.required = isMicroFinance;
    }

    if (manualPayment) {
      manualPayment.required = !isMicroFinance && isManual;
    }

    updateInterestRate();
  };

  const updateDeductions = () => {
    const principalValue = numericValue(principal);
    const termsValue = Number(terms?.value) || 0;

    if (!principalValue || !termsValue) {
      processingFee.textContent = "₱0.00";
      insurance.textContent = "₱0.00";
      netProceeds.textContent = "₱0.00";
      calculationNote.textContent =
        "Enter both principal and terms to calculate deductions.";
      return;
    }

    const fee = principalValue * 0.02;

    // This follows the current loan specification exactly:
    // insurance = (principal / 1000) * 1.2 * terms
    const insuranceValue = (principalValue / 1000) * 1.2 * termsValue;
    const proceeds = principalValue - fee - insuranceValue - 400;

    processingFee.textContent = money(fee);
    insurance.textContent = money(insuranceValue);
    netProceeds.textContent = money(proceeds);
    calculationNote.textContent =
      "Computed from the current principal and term values.";
  };

  const showValidation = (message) => {
    validationBox.textContent = message;
    validationBox.hidden = !message;
  };

  const validateBeforeReview = () => {
    showValidation("");

    const requiredFields = [
      form.querySelector("#loan-member"),
      loanType,
      collateral,
      principal,
      interestRate,
      terms,
      startDate,
    ];

    const missing = requiredFields.find(
      (field) => field && !field.value.trim()
    );

    if (missing) {
      missing.focus();
      showValidation("Please complete all required loan fields.");
      return false;
    }

    if (numericValue(principal) <= 0) {
      principal.focus();
      showValidation("Principal amount must be greater than zero.");
      return false;
    }

    if (Number(terms.value) <= 0 || !Number.isInteger(Number(terms.value))) {
      terms.focus();
      showValidation("Terms must be a positive whole number of months.");
      return false;
    }

    if (Number(interestRate.value) <= 0) {
      interestRate.focus();
      showValidation("Interest rate must be greater than zero.");
      return false;
    }

    const isMicroFinance = loanType.value === "Micro-Finance Loan";

    if (isMicroFinance && !paymentFrequency.value) {
      paymentFrequency.focus();
      showValidation(
        "Payment frequency is required for Micro-Finance Loan."
      );
      return false;
    }

    if (!isMicroFinance && !amortizationType.value) {
      amortizationType.focus();
      showValidation("Amortization type is required.");
      return false;
    }

    if (
      !isMicroFinance &&
      amortizationType.value === "Manual" &&
      numericValue(manualPayment) <= 0
    ) {
      manualPayment.focus();
      showValidation(
        "Manual payment must be greater than zero for Manual amortization."
      );
      return false;
    }

    return true;
  };

  principal?.addEventListener("input", updateDeductions);
  terms?.addEventListener("input", updateDeductions);

  interestRate?.addEventListener("input", () => {
    interestRateDirty = true;
  });

  loanType?.addEventListener("change", () => {
    interestRateDirty = false;
    updateInterestRate();
    updateConditionalFields();
  });

  collateral?.addEventListener("change", updateConditionalFields);
  amortizationType?.addEventListener("change", updateConditionalFields);

  reviewButton?.addEventListener("click", () => {
    if (!validateBeforeReview()) {
      return;
    }

    form.submit();
  });

  setHidden(paymentFrequencyGroup, true);
  setHidden(manualPaymentGroup, true);
  setHidden(realPropertyGroup, true);

  updateInterestRate();
  updateConditionalFields();
  updateDeductions();
})();
