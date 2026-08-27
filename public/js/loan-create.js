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

  const previewStatus = form.querySelector(
    "[data-amortization-preview-status]"
  );
  const previewWrap = form.querySelector(
    "[data-amortization-preview]"
  );
  const previewBody = form.querySelector(
    "[data-amortization-preview-body]"
  );

  let interestRateDirty = false;

  const money = (value) =>
    Number.isFinite(value)
      ? value.toLocaleString("en-PH", {
          style: "currency",
          currency: "PHP",
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
      : "₱0.00";

  const numericValue = (input) => {
    if (!input) return 0;
    return Number(input.value.replace(/,/g, "")) || 0;
  };

  const formatMoneyInput = (input) => {
    if (!input) return;

    const raw = input.value.replace(/[^\d.]/g, "");
    const parts = raw.split(".");
    let whole = parts.shift() ?? "";
    const decimal = parts.join("").substring(0, 2);

    whole = whole.replace(/^0+(?=\d)/, "");

    if (!whole && raw !== "") {
      whole = "0";
    }

    whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ",");

    input.value = decimal
      ? `${whole}.${decimal}`
      : whole;
  };

  const setHidden = (element, hidden) => {
    if (!element) return;

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

    interestRate.value =
      loanType.value === "Micro-Finance Loan"
        ? "5"
        : "2";
  };

  const updateConditionalFields = () => {
    const isMicroFinance =
      loanType?.value === "Micro-Finance Loan";
    const isManual =
      amortizationType?.value === "Manual";
    const isRealProperty =
      collateral?.value === "Real Property";

    setHidden(
      standardAmortization,
      isMicroFinance
    );
    setHidden(
      paymentFrequencyGroup,
      !isMicroFinance
    );
    setHidden(
      manualPaymentGroup,
      isMicroFinance || !isManual
    );
    setHidden(
      realPropertyGroup,
      !isRealProperty
    );

    if (amortizationType) {
      amortizationType.required =
        !isMicroFinance;

      if (isMicroFinance) {
        amortizationType.value = "";
      }
    }

    if (paymentFrequency) {
      paymentFrequency.required =
        isMicroFinance;
    }

    if (manualPayment) {
      manualPayment.required =
        !isMicroFinance && isManual;
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
    const insuranceValue =
      (principalValue / 1000) * 1.2 * termsValue;
    const proceeds =
      principalValue
      - fee
      - insuranceValue
      - 400;

    processingFee.textContent = money(fee);
    insurance.textContent = money(insuranceValue);
    netProceeds.textContent = money(proceeds);
    calculationNote.textContent =
      "Computed from the current principal and term values.";
  };

  const toDate = (value) => {
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime())
      ? null
      : date;
  };

  const addMonths = (date, months) => {
    const result = new Date(date);
    const day = result.getDate();

    result.setMonth(result.getMonth() + months);

    if (result.getDate() !== day) {
      result.setDate(0);
    }

    return result;
  };

  const addDays = (date, days) => {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
  };

  const formatDate = (date) => {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, "0");
    const day = `${date.getDate()}`.padStart(2, "0");
    return `${year}-${month}-${day}`;
  };

  const previewAmortization = () => {
    if (!previewBody || !previewWrap || !previewStatus) {
      return;
    }

    const principalValue = numericValue(principal);
    const interestValue =
      Number(interestRate?.value) || 0;
    const termValue = Number(terms?.value) || 0;
    const start = toDate(startDate?.value || "");
    const isMicroFinance =
      loanType?.value === "Micro-Finance Loan";
    const type = amortizationType?.value || "";
    const frequency = paymentFrequency?.value || "";
    const manual =
      numericValue(manualPayment);

    const valid =
      principalValue > 0
      && interestValue > 0
      && termValue > 0
      && Number.isInteger(termValue)
      && start
      && (
        isMicroFinance
          ? Boolean(frequency)
          : Boolean(type)
      )
      && (
        type !== "Manual" || manual > 0
      );

    if (!valid) {
      previewBody.replaceChildren();
      previewWrap.hidden = true;
      previewStatus.textContent =
        "Enter the required loan details to generate the schedule.";
      return;
    }

    const rows = [];
    const rounded = (value) =>
      Math.round((value + Number.EPSILON) * 100) / 100;

    let totalPeriods = termValue;
    let periodRate = interestValue / 100;
    let periodPrincipal =
      principalValue / termValue;

    if (isMicroFinance) {
      const multiplier =
        frequency === "Weekly"
          ? 4
          : frequency === "Bi-Monthly"
            ? 2
            : 1;

      totalPeriods =
        termValue * multiplier;

      periodPrincipal =
        principalValue / totalPeriods;

      periodRate =
        (interestValue / 100) / multiplier;
    }

    let balance = principalValue;

    for (let period = 1; period <= totalPeriods; period++) {
      let dueDate;

      if (isMicroFinance) {
        dueDate =
          frequency === "Weekly"
            ? addDays(start, period * 7)
            : frequency === "Bi-Monthly"
              ? addDays(start, period * 15)
              : addMonths(start, period);
      } else {
        dueDate = addMonths(start, period);
      }

      let interest;
      let principalPart;

      if (
        isMicroFinance
      ) {
        principalPart =
          Math.min(
            periodPrincipal,
            Math.max(0, balance)
          );
        interest =
          principalValue * periodRate;
      } else if (
        type === "Straight-line"
      ) {
        principalPart = periodPrincipal;
        interest =
          principalValue * periodRate;
      } else if (
        type === "Diminishing balance"
      ) {
        principalPart = periodPrincipal;
        interest = balance * periodRate;
      } else {
        interest = balance * periodRate;
        principalPart = Math.max(
          0,
          manual - interest
        );
      }

      principalPart = Math.min(
        principalPart,
        Math.max(0, balance)
      );

      interest = Math.max(0, interest);
      balance = Math.max(
        0,
        balance - principalPart
      );

      const payment =
        rounded(principalPart)
        + rounded(interest);

      rows.push({
        period,
        dueDate,
        principal: rounded(principalPart),
        interest: rounded(interest),
        payment: rounded(payment),
      });
    }

    // Match server-side final-principal reconciliation.
    const totalPrincipal = rows.reduce(
      (sum, row) => sum + row.principal,
      0
    );

    if (rows.length > 0) {
      const difference =
        rounded(principalValue - totalPrincipal);

      if (Math.abs(difference) >= 0.005) {
        rows[rows.length - 1].principal =
          rounded(
            Math.max(
              0,
              rows[rows.length - 1].principal
                + difference
            )
          );

        rows[rows.length - 1].payment =
          rounded(
            rows[rows.length - 1].principal
              + rows[rows.length - 1].interest
          );
      }
    }

    previewBody.replaceChildren(
      ...rows.map((row) => {
        const tr = document.createElement("tr");

        const values = [
          row.period,
          row.dueDate,
          money(row.principal),
          money(row.interest),
          money(row.payment),
          "Pending",
        ];

        values.forEach((value, index) => {
          const td = document.createElement("td");
          td.textContent = value;

          if (index >= 2 && index <= 4) {
            td.classList.add("u-text-right");
          }

          tr.appendChild(td);
        });

        return tr;
      })
    );

    previewWrap.hidden = false;
    previewStatus.textContent =
      `${rows.length} payment period${rows.length === 1 ? "" : "s"} generated automatically.`;
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
      showValidation(
        "Please complete all required loan fields."
      );
      return false;
    }

    if (numericValue(principal) <= 0) {
      principal.focus();
      showValidation(
        "Principal amount must be greater than zero."
      );
      return false;
    }

    if (
      Number(terms.value) <= 0
      || !Number.isInteger(Number(terms.value))
    ) {
      terms.focus();
      showValidation(
        "Terms must be a positive whole number of months."
      );
      return false;
    }

    if (Number(interestRate.value) <= 0) {
      interestRate.focus();
      showValidation(
        "Interest rate must be greater than zero."
      );
      return false;
    }

    const isMicroFinance =
      loanType.value === "Micro-Finance Loan";

    if (
      isMicroFinance
      && !paymentFrequency.value
    ) {
      paymentFrequency.focus();
      showValidation(
        "Payment frequency is required for Micro-Finance Loan."
      );
      return false;
    }

    if (
      !isMicroFinance
      && !amortizationType.value
    ) {
      amortizationType.focus();
      showValidation(
        "Amortization type is required."
      );
      return false;
    }

    if (
      !isMicroFinance
      && amortizationType.value === "Manual"
      && numericValue(manualPayment) <= 0
    ) {
      manualPayment.focus();
      showValidation(
        "Manual payment must be greater than zero for Manual amortization."
      );
      return false;
    }

    return true;
  };

  form
    .querySelectorAll("[data-loan-money]")
    .forEach((input) => {
      input.addEventListener("input", () => {
        formatMoneyInput(input);
        updateDeductions();
        previewAmortization();
      });

      formatMoneyInput(input);
    });

  terms?.addEventListener("input", () => {
    updateDeductions();
    previewAmortization();
  });

  interestRate?.addEventListener("input", () => {
    interestRateDirty = true;
    previewAmortization();
  });

  loanType?.addEventListener("change", () => {
    interestRateDirty = false;
    updateInterestRate();
    updateConditionalFields();
    previewAmortization();
  });

  collateral?.addEventListener("change", updateConditionalFields);
  amortizationType?.addEventListener("change", () => {
    updateConditionalFields();
    previewAmortization();
  });
  paymentFrequency?.addEventListener("change", previewAmortization);
  startDate?.addEventListener("change", previewAmortization);

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
  previewAmortization();
})();
