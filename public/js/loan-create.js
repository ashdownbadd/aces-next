(() => {
  "use strict";

  const form =
    document.querySelector(
      "#loan-create-form"
    );

  if (!form) {
    return;
  }

  const memberPicker =
    form.querySelector("[data-member-picker]");

  const memberSearchInput =
    form.querySelector("[data-member-search]");

  const memberIdInput =
    form.querySelector("[data-member-id]");

  const memberResults =
    form.querySelector("[data-member-results]");

  const memberSelected =
    form.querySelector("[data-member-selected]");

  let memberSearchTimer = null;
  let memberSearchController = null;
  let activeMemberIndex = -1;

  const escapeHtml = (value) =>
    value.replace(/[&<>"']/g, (character) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[character]));

  const clearMemberResults = () => {
    if (!memberResults) {
      return;
    }

    memberResults.replaceChildren();
    memberResults.hidden = true;
    memberSearchInput?.setAttribute(
      "aria-expanded",
      "false"
    );
    activeMemberIndex = -1;
  };

  const selectMember = (member) => {
    if (!memberSearchInput || !memberIdInput) {
      return;
    }

    memberIdInput.value = String(member.id);
    memberSearchInput.value =
      `${member.member_number} — ${member.name}`;

    if (memberSelected) {
      memberSelected.textContent =
        `Selected member: ${member.member_number} — ${member.name}`;
      memberSelected.hidden = false;
    }

    clearMemberResults();
  };

  const renderMemberResults = (members) => {
    if (!memberResults) {
      return;
    }

    memberResults.replaceChildren();
    activeMemberIndex = -1;

    if (members.length === 0) {
      const empty =
        document.createElement("div");

      empty.className =
        "loan-member-picker__empty";

      empty.textContent =
        "No active members found.";

      memberResults.appendChild(empty);
      memberResults.hidden = false;

      memberSearchInput?.setAttribute(
        "aria-expanded",
        "true"
      );

      return;
    }

    members.forEach((member, index) => {
      const button =
        document.createElement("button");

      button.type = "button";
      button.className =
        "loan-member-picker__option";
      button.dataset.memberIndex =
        String(index);

      button.setAttribute(
        "role",
        "option"
      );

      button.innerHTML = `
        <span class="loan-member-picker__option-number">
          ${escapeHtml(String(member.member_number))}
        </span>
        <span class="loan-member-picker__option-name">
          ${escapeHtml(String(member.name))}
        </span>
      `;

      button.addEventListener(
        "mousedown",
        (event) => {
          event.preventDefault();
        }
      );

      button.addEventListener(
        "click",
        () => selectMember(member)
      );

      memberResults.appendChild(button);
    });

    memberResults.hidden = false;

    memberSearchInput?.setAttribute(
      "aria-expanded",
      "true"
    );
  };

  const fetchMembers = async () => {
    if (!memberSearchInput || !memberResults) {
      return;
    }

    const query =
      memberSearchInput.value.trim();

    if (query.length < 2) {
      clearMemberResults();
      return;
    }

    memberSearchController?.abort();
    memberSearchController =
      new AbortController();

    try {
      const url =
        `/loans/members/search?q=${
          encodeURIComponent(query)
        }`;

      const response = await fetch(
        url,
        {
          headers: {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
          signal:
            memberSearchController.signal,
        }
      );

      if (!response.ok) {
        throw new Error(
          `Member search failed: ${response.status}`
        );
      }

      const data =
        await response.json();

      if (
        memberSearchController.signal.aborted
        || memberSearchInput.value.trim()
          !== query
      ) {
        return;
      }

      renderMemberResults(
        Array.isArray(data.members)
          ? data.members
          : []
      );
    } catch (error) {
      if (error.name === "AbortError") {
        return;
      }

      console.error(
        "Unable to search members.",
        error
      );
    }
  };

  const setActiveMemberOption =
    (index) => {
      const options =
        Array.from(
          memberResults?.querySelectorAll(
            ".loan-member-picker__option"
          ) || []
        );

      if (options.length === 0) {
        activeMemberIndex = -1;
        return;
      }

      activeMemberIndex =
        Math.max(
          0,
          Math.min(
            index,
            options.length - 1
          )
        );

      options.forEach(
        (option, optionIndex) => {
          const active =
            optionIndex === activeMemberIndex;

          option.classList.toggle(
            "is-active",
            active
          );

          option.setAttribute(
            "aria-selected",
            active ? "true" : "false"
          );
        }
      );

      options[activeMemberIndex]
        ?.scrollIntoView({
          block: "nearest",
        });
    };

  memberSearchInput?.addEventListener(
    "input",
    () => {
      memberIdInput.value = "";

      if (memberSelected) {
        memberSelected.hidden = true;
      }

      window.clearTimeout(
        memberSearchTimer
      );

      memberSearchTimer =
        window.setTimeout(
          fetchMembers,
          250
        );
    }
  );

  memberSearchInput?.addEventListener(
    "keydown",
    (event) => {
      if (
        event.key === "ArrowDown"
      ) {
        event.preventDefault();
        setActiveMemberOption(
          activeMemberIndex + 1
        );
        return;
      }

      if (
        event.key === "ArrowUp"
      ) {
        event.preventDefault();
        setActiveMemberOption(
          activeMemberIndex - 1
        );
        return;
      }

      if (
        event.key === "Enter"
        && activeMemberIndex >= 0
      ) {
        event.preventDefault();

        const option =
          memberResults?.querySelector(
            `[data-member-index="${activeMemberIndex}"]`
          );

        option?.click();
        return;
      }

      if (event.key === "Escape") {
        clearMemberResults();
      }
    }
  );

  document.addEventListener(
    "click",
    (event) => {
      if (
        memberPicker
        && !memberPicker.contains(
          event.target
        )
      ) {
        clearMemberResults();
      }
    }
  );

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

  const displayDate = (date) => {
    return date.toLocaleDateString(
      "en-PH",
      {
        month: "short",
        day: "2-digit",
        year: "numeric",
      }
    );
  };

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
          dueDate,
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
          dueDate,
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
          displayDate(row.dueDate),
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
