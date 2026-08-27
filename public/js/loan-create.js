(() => {
  "use strict";

  const form =
    document.querySelector(
      "#loan-create-form"
    );

  if (!form) {
    return;
  }

  const loanType =
    form.querySelector("#loan-type");

  const collateral =
    form.querySelector("#loan-collateral");

  const principal =
    form.querySelector("#principal-amount");

  const interestRate =
    form.querySelector("#interest-rate");

  const amortizationType =
    form.querySelector("#amortization-type");

  const paymentFrequency =
    form.querySelector("#payment-frequency");

  const manualPayment =
    form.querySelector("#manual-payment");

  const terms =
    form.querySelector("#terms-months");

  const startDate =
    form.querySelector("#start-date");

  const standardAmortization =
    form.querySelector(
      "[data-standard-amortization]"
    );

  const paymentFrequencyGroup =
    form.querySelector(
      "[data-payment-frequency]"
    );

  const manualPaymentGroup =
    form.querySelector(
      "[data-manual-payment]"
    );

  const realPropertyGroup =
    form.querySelector(
      "[data-real-property]"
    );

  const processingFee =
    form.querySelector(
      "[data-processing-fee]"
    );

  const insurance =
    form.querySelector(
      "[data-insurance]"
    );

  const netProceeds =
    form.querySelector(
      "[data-net-proceeds]"
    );

  const calculationNote =
    form.querySelector(
      "[data-calculation-note]"
    );

  const validationBox =
    form.querySelector(
      "[data-loan-validation]"
    );

  const reviewButton =
    form.querySelector(
      "[data-review-loan]"
    );

  const previewStatus =
    form.querySelector(
      "[data-amortization-preview-status]"
    );

  const previewWrap =
    form.querySelector(
      "[data-amortization-preview]"
    );

  const previewBody =
    form.querySelector(
      "[data-amortization-preview-body]"
    );

  let interestRateDirty = false;

  const money = (value) =>
    Number.isFinite(value)
      ? value.toLocaleString(
          "en-PH",
          {
            style: "currency",
            currency: "PHP",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }
        )
      : "₱0.00";

  const numberValue = (input) =>
    input
      ? Number(
          input.value.replace(/,/g, "")
        ) || 0
      : 0;

  const formatMoneyInput = (input) => {
    if (!input) return;

    let value =
      input.value.replace(
        /[^\d.]/g,
        ""
      );

    const parts =
      value.split(".");

    let whole =
      parts.shift() ?? "";

    const decimal =
      parts.join("").substring(0, 2);

    whole = whole.replace(
      /^0+(?=\d)/,
      ""
    );

    whole = whole.replace(
      /\B(?=(\d{3})+(?!\d))/g,
      ","
    );

    input.value = decimal
      ? `${whole}.${decimal}`
      : whole;
  };

  const setHidden = (
    element,
    hidden
  ) => {
    if (!element) return;

    element.hidden = hidden;

    element
      .querySelectorAll(
        "input, select, textarea"
      )
      .forEach((input) => {
        input.disabled = hidden;

        if (hidden) {
          input.required = false;
        }
      });
  };

  const updateInterestRate = () => {
    if (
      !loanType
      || !interestRate
      || interestRateDirty
    ) {
      return;
    }

    interestRate.value =
      loanType.value === "Micro-Finance Loan"
        ? "5"
        : "2";
  };

  const updateConditionalFields = () => {
    const micro =
      loanType?.value === "Micro-Finance Loan";

    const manual =
      amortizationType?.value === "Manual";

    const realProperty =
      collateral?.value === "Real Property";

    setHidden(
      standardAmortization,
      micro
    );

    setHidden(
      paymentFrequencyGroup,
      !micro
    );

    setHidden(
      manualPaymentGroup,
      micro || !manual
    );

    setHidden(
      realPropertyGroup,
      !realProperty
    );

    if (amortizationType) {
      amortizationType.required = !micro;
    }

    if (paymentFrequency) {
      paymentFrequency.required = micro;
    }

    if (manualPayment) {
      manualPayment.required =
        !micro && manual;
    }

    updateInterestRate();
  };

  const updateDeductions = () => {
    const principalValue =
      numberValue(principal);

    const termsValue =
      Number(terms?.value) || 0;

    if (
      !principalValue
      || !termsValue
    ) {
      processingFee.textContent =
        "₱0.00";
      insurance.textContent =
        "₱0.00";
      netProceeds.textContent =
        "₱0.00";

      calculationNote.textContent =
        "Enter both principal and terms to calculate deductions.";

      return;
    }

    const fee =
      principalValue * 0.02;

    const insuranceValue =
      (principalValue / 1000)
      * 1.2
      * termsValue;

    const proceeds =
      principalValue
      - fee
      - insuranceValue
      - 400;

    processingFee.textContent =
      money(fee);

    insurance.textContent =
      money(insuranceValue);

    netProceeds.textContent =
      money(proceeds);

    calculationNote.textContent =
      "Computed from the current principal and term values.";
  };

  const showValidation = (message) => {
    validationBox.textContent =
      message;

    validationBox.hidden =
      !message;
  };

  const validateBeforeReview = () => {
    showValidation("");

    const required = [
      form.querySelector(
        "#loan-member"
      ),
      loanType,
      collateral,
      principal,
      interestRate,
      terms,
      startDate,
    ];

    const missing =
      required.find(
        (field) =>
          field
          && !field.value.trim()
      );

    if (missing) {
      missing.focus();

      showValidation(
        "Please complete all required loan fields."
      );

      return false;
    }

    if (
      numberValue(principal)
      <= 0
    ) {
      principal.focus();

      showValidation(
        "Principal amount must be greater than zero."
      );

      return false;
    }

    if (
      Number(terms.value) <= 0
      || !Number.isInteger(
        Number(terms.value)
      )
    ) {
      terms.focus();

      showValidation(
        "Terms must be a positive whole number of months."
      );

      return false;
    }

    if (
      Number(interestRate.value)
      <= 0
    ) {
      interestRate.focus();

      showValidation(
        "Interest rate must be greater than zero."
      );

      return false;
    }

    const micro =
      loanType.value === "Micro-Finance Loan";

    if (
      micro
      && !paymentFrequency.value
    ) {
      paymentFrequency.focus();

      showValidation(
        "Payment frequency is required for Micro-Finance Loan."
      );

      return false;
    }

    if (
      !micro
      && !amortizationType.value
    ) {
      amortizationType.focus();

      showValidation(
        "Amortization type is required."
      );

      return false;
    }

    if (
      !micro
      && amortizationType.value === "Manual"
      && numberValue(manualPayment) <= 0
    ) {
      manualPayment.focus();

      showValidation(
        "Manual payment must be greater than zero."
      );

      return false;
    }

    return true;
  };

  const toDate = (value) => {
    const date =
      new Date(
        `${value}T00:00:00`
      );

    return Number.isNaN(
      date.getTime()
    )
      ? null
      : date;
  };

  const addMonths = (
    date,
    months
  ) => {
    const result =
      new Date(date);

    const originalDay =
      result.getDate();

    result.setMonth(
      result.getMonth() + months
    );

    if (
      result.getDate()
      !== originalDay
    ) {
      result.setDate(0);
    }

    return result;
  };

  const addDays = (
    date,
    days
  ) => {
    const result =
      new Date(date);

    result.setDate(
      result.getDate() + days
    );

    return result;
  };

  const roundMoney = (value) =>
    Math.round(
      (value + Number.EPSILON)
      * 100
    ) / 100;

  const generatePreview = () => {
    if (
      !previewBody
      || !previewWrap
      || !previewStatus
    ) {
      return;
    }

    const principalValue =
      numberValue(principal);

    const rate =
      Number(interestRate?.value)
      || 0;

    const term =
      Number(terms?.value)
      || 0;

    const start =
      toDate(startDate?.value || "");

    const micro =
      loanType?.value
      === "Micro-Finance Loan";

    const type =
      amortizationType?.value
      || "";

    const frequency =
      paymentFrequency?.value
      || "";

    const manual =
      numberValue(manualPayment);

    const valid =
      principalValue > 0
      && rate >= 0
      && Number.isInteger(term)
      && term > 0
      && start
      && (
        micro
          ? Boolean(frequency)
          : Boolean(type)
      )
      && (
        type !== "Manual"
        || manual > 0
      );

    if (!valid) {
      previewBody.replaceChildren();
      previewWrap.hidden = true;

      previewStatus.textContent =
        "Enter the required loan details to preview the schedule.";

      return;
    }

    const rows = [];

    if (micro) {
      const multiplier =
        frequency === "Weekly"
          ? 4
          : frequency === "Bi-Monthly"
            ? 2
            : 1;

      const periods =
        term * multiplier;

      const principalPerPeriod =
        principalValue / periods;

      const periodRate =
        (rate / 100)
        / multiplier;

      const interestPerPeriod =
        principalValue
        * periodRate;

      let balance =
        principalValue;

      for (
        let period = 1;
        period <= periods;
        period++
      ) {
        let dueDate;

        if (
          frequency === "Weekly"
        ) {
          dueDate =
            addDays(
              start,
              period * 7
            );
        } else if (
          frequency === "Bi-Monthly"
        ) {
          dueDate =
            addDays(
              start,
              period * 15
            );
        } else {
          dueDate =
            addMonths(
              start,
              period
            );
        }

        const principalPart =
          Math.min(
            principalPerPeriod,
            Math.max(0, balance)
          );

        const interest =
          interestPerPeriod;

        balance =
          Math.max(
            0,
            balance - principalPart
          );

        rows.push({
          period,
          dueDate:
            dueDate
              .toISOString()
              .slice(0, 10),
          principal:
            roundMoney(principalPart),
          interest:
            roundMoney(interest),
          payment:
            roundMoney(
              principalPart
              + interest
            ),
        });
      }
    } else {
      const principalPerPeriod =
        principalValue / term;

      const monthlyRate =
        rate / 100;

      let balance =
        principalValue;

      for (
        let period = 1;
        period <= term;
        period++
      ) {
        const dueDate =
          addMonths(
            start,
            period
          );

        let interest;
        let principalPart;

        if (
          type === "Straight-line"
        ) {
          interest =
            principalValue
            * monthlyRate;

          principalPart =
            principalPerPeriod;
        } else if (
          type === "Diminishing balance"
        ) {
          interest =
            balance
            * monthlyRate;

          principalPart =
            principalPerPeriod;
        } else {
          interest =
            balance
            * monthlyRate;

          principalPart =
            Math.max(
              0,
              manual - interest
            );
        }

        principalPart =
          Math.min(
            principalPart,
            Math.max(0, balance)
          );

        interest =
          Math.max(0, interest);

        balance =
          Math.max(
            0,
            balance - principalPart
          );

        rows.push({
          period,
          dueDate:
            dueDate
              .toISOString()
              .slice(0, 10),
          principal:
            roundMoney(principalPart),
          interest:
            roundMoney(interest),
          payment:
            roundMoney(
              principalPart
              + interest
            ),
        });
      }
    }

    if (rows.length > 0) {
      const totalPrincipal =
        roundMoney(
          rows.reduce(
            (sum, row) =>
              sum + row.principal,
            0
          )
        );

      const difference =
        roundMoney(
          principalValue
          - totalPrincipal
        );

      if (
        Math.abs(difference)
        >= 0.005
      ) {
        const last =
          rows[rows.length - 1];

        last.principal =
          roundMoney(
            Math.max(
              0,
              last.principal
              + difference
            )
          );

        last.payment =
          roundMoney(
            last.principal
            + last.interest
          );
      }
    }

    previewBody.replaceChildren(
      ...rows.map((row) => {
        const tr =
          document.createElement(
            "tr"
          );

        [
          row.period,
          row.dueDate,
          money(row.principal),
          money(row.interest),
          money(row.payment),
          "Pending",
        ].forEach(
          (value, index) => {
            const td =
              document.createElement(
                "td"
              );

            td.textContent =
              value;

            tr.appendChild(td);
          }
        );

        return tr;
      })
    );

    previewWrap.hidden = false;

    previewStatus.textContent =
      `${rows.length} payment period${
        rows.length === 1
          ? ""
          : "s"
      } generated automatically.`;
  };

  form
    .querySelectorAll(
      "[data-loan-money]"
    )
    .forEach((input) => {
      input.addEventListener(
        "input",
        () => {
          formatMoneyInput(input);
          updateDeductions();
          generatePreview();
        }
      );

      formatMoneyInput(input);
    });

  [
    terms,
    interestRate,
    startDate,
  ].forEach((input) => {
    input?.addEventListener(
      "input",
      () => {
        updateDeductions();
        generatePreview();
      }
    );

    input?.addEventListener(
      "change",
      () => {
        updateDeductions();
        generatePreview();
      }
    );
  });

  interestRate?.addEventListener(
    "input",
    () => {
      interestRateDirty = true;
      generatePreview();
    }
  );

  loanType?.addEventListener(
    "change",
    () => {
      interestRateDirty = false;
      updateInterestRate();
      updateConditionalFields();
      generatePreview();
    }
  );

  collateral?.addEventListener(
    "change",
    updateConditionalFields
  );

  amortizationType?.addEventListener(
    "change",
    () => {
      updateConditionalFields();
      generatePreview();
    }
  );

  paymentFrequency?.addEventListener(
    "change",
    generatePreview
  );

  form.addEventListener(
    "submit",
    () => {
      form
        .querySelectorAll(
          "[data-loan-money]"
        )
        .forEach((input) => {
          input.value =
            input.value.replace(
              /,/g,
              ""
            );
        });
    }
  );

  reviewButton?.addEventListener(
    "click",
    (event) => {
      event.preventDefault();

      if (!validateBeforeReview()) {
        return;
      }

      if (
        typeof form.requestSubmit
        === "function"
      ) {
        form.requestSubmit();
      } else {
        form.submit();
      }
    }
  );

  setHidden(
    paymentFrequencyGroup,
    true
  );

  setHidden(
    manualPaymentGroup,
    true
  );

  setHidden(
    realPropertyGroup,
    true
  );

  updateInterestRate();
  updateConditionalFields();
  updateDeductions();
  generatePreview();
})();
