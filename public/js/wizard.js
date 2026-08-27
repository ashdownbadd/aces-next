console.log("wizard.js loaded");

const Helpers = {
  onInput(input, callback) {
    input.addEventListener("input", () => {
      input.value = callback(input.value);
    });
  },

  trim(value) {
    return value.replace(/\s+/g, " ").trimStart();
  },

  titleCase(value) {
    const particles = [
      "de", "del", "dela", "de la", "la",
      "van", "von", "bin", "ibn",
    ];

    return value
      .toLowerCase()
      .split(" ")
      .map((word) => {
        if (particles.includes(word)) {
          return word;
        }

        return word.charAt(0).toUpperCase() + word.slice(1);
      })
      .join(" ");
  },

  digits(value) {
    return value.replace(/\D/g, "");
  },

  limit(value, max) {
    return value.substring(0, max);
  },
};

const InputTypes = {
  personName(input) {
    Helpers.onInput(input, (value) => {
      value = Helpers.trim(value);
      value = value.replace(/[^A-Za-zÀ-ÿ'.\-\s]/g, "");
      return Helpers.titleCase(value);
    });
  },

  title(input) {
    Helpers.onInput(input, (value) => {
      value = Helpers.trim(value);
      value = value.replace(/[^A-Za-zÀ-ÿ0-9.,'()\-\/#\s]/g, "");
      return Helpers.titleCase(value);
    });
  },

  suffix(input) {
    const sanitize = (value) =>
      value
        .replace(/[^A-Za-z0-9.\-\s]/g, "")
        .replace(/\s+/g, " ")
        .trimStart();

    // Keep the user's exact input while typing.
    // Canonical formatting happens only after leaving the field.
    input.addEventListener("input", () => {
      input.value = sanitize(input.value);
    });

    input.addEventListener("blur", () => {
      const value = sanitize(input.value);
      const normalized = value.toLowerCase();

      if (
        normalized === "jr"
        || normalized === "jr."
      ) {
        input.value = "Jr.";
        return;
      }

      if (
        normalized === "sr"
        || normalized === "sr."
      ) {
        input.value = "Sr.";
        return;
      }

      const roman =
        /^(?:I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII|XIII|XIV|XV|XVI|XVII|XVIII|XIX|XX)$/i;

      if (roman.test(value)) {
        input.value = normalized.toUpperCase();
      }
    });
  },

  phone(input) {
    const max =
      Number(input.dataset.maxLength ?? 11);

    Helpers.onInput(input, (value) =>
      Helpers.limit(
        Helpers.digits(value),
        max
      )
    );

    input.addEventListener("blur", () => {
      if (
        input.value
        && !/^09\d{9}$/.test(input.value)
      ) {
        input.setCustomValidity(
          "Enter a valid Philippine mobile number."
        );
      } else {
        input.setCustomValidity("");
      }
    });
  },

  telephone(input) {
    const max =
      Number(input.dataset.maxLength ?? 10);

    const format = (value) => {
      const clean = Helpers.limit(
        Helpers.digits(value),
        max
      );

      if (!clean) {
        return "";
      }

      if (clean.startsWith("02")) {
        if (clean.length <= 2) {
          return `(${clean}`;
        }

        return clean.length <= 6
          ? `(${clean.slice(0, 2)}) ${clean.slice(2)}`
          : `(${clean.slice(0, 2)}) ${clean.slice(2, 6)}-${clean.slice(6)}`;
      }

      if (clean.length <= 3) {
        return `(${clean}`;
      }

      return clean.length <= 7
        ? `(${clean.slice(0, 3)}) ${clean.slice(3)}`
        : `(${clean.slice(0, 3)}) ${clean.slice(3, 7)}-${clean.slice(7)}`;
    };

    input.addEventListener("input", () => {
      input.value = format(input.value);
      input.setCustomValidity("");
    });

    input.addEventListener("blur", () => {
      const clean =
        Helpers.digits(input.value);

      if (!clean) {
        input.setCustomValidity("");
        return;
      }

      input.setCustomValidity(
        clean.length >= 7
        && clean.length <= max
          ? ""
          : "Enter a valid telephone number."
      );
    });
  },

  email(input) {
    Helpers.onInput(input, (value) =>
      value
        .replace(/\s+/g, "")
        .toLowerCase()
    );
  },

  zip(input) {
    const max =
      Number(input.dataset.maxLength ?? 4);

    Helpers.onInput(input, (value) =>
      Helpers.limit(
        Helpers.digits(value),
        max
      )
    );
  },

  money(input) {
    input.addEventListener("input", () => {
      let value =
        input.value.replace(/[^\d.]/g, "");

      const parts = value.split(".");
      let whole = parts.shift() ?? "";
      const decimal = parts
        .join("")
        .substring(0, 2);

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
    });
  },

  birthdate(input) {
    input.max =
      new Date().toISOString().split("T")[0];
  },
};

const ConditionalFields = {
  setDisabled(group, disabled) {
    if (!group) {
      return;
    }

    group
      .querySelectorAll(
        "input, select, textarea"
      )
      .forEach((field) => {
        field.disabled = disabled;

        if (disabled) {
          field.required = false;
        }
      });

    group.classList.toggle(
      "is-disabled",
      disabled
    );
  },

  livelihood() {
    const status =
      document.querySelector(
        "#employment_status"
      );

    if (!status) {
      return;
    }

    const occupation =
      document
        .querySelector("#occupation")
        ?.closest(".form-group");

    const employer =
      document
        .querySelector("#employer")
        ?.closest(".form-group");

    const income =
      document
        .querySelector("#monthly_income")
        ?.closest(".form-group");

    const nonWorking =
      status.value === "student"
      || status.value === "unemployed";

    const retired =
      status.value === "retired";

    this.setDisabled(
      occupation,
      nonWorking
    );

    this.setDisabled(
      employer,
      nonWorking || retired
    );

    this.setDisabled(
      income,
      nonWorking || retired
    );
  },

  education() {
    const attainment =
      document.querySelector(
        "#highest_educational_attainment"
      );

    if (!attainment) {
      return;
    }

    const school =
      document
        .querySelector("#school_name")
        ?.closest(".form-group");

    const year =
      document
        .querySelector("#graduation_year")
        ?.closest(".form-group");

    const noFormal =
      attainment.value === "no_formal_education";

    this.setDisabled(
      school,
      noFormal
    );

    this.setDisabled(
      year,
      noFormal
    );
  },
};

const WizardAutoSave = {
  key:
    "aces:member-registration:draft:v1",

  isEdit() {
    return new URLSearchParams(
      window.location.search
    ).has("edit");
  },

  isExplicitNew() {
    return new URLSearchParams(
      window.location.search
    ).get("new") === "1";
  },

  form() {
    return document.querySelector(
      ".wizard__body form"
    );
  },

  read() {
    try {
      const raw =
        localStorage.getItem(this.key);

      return raw
        ? JSON.parse(raw)
        : {};
    } catch {
      return {};
    }
  },

  write(form) {
    const draft = {
      fields: {},
      savedAt: Date.now(),
    };

    form.querySelectorAll(
      "input[name], select[name], textarea[name]"
    ).forEach((field) => {
      if (field.type === "file") {
        return;
      }

      draft.fields[field.name] =
        field.type === "checkbox"
          ? field.checked
          : field.value;
    });

    try {
      localStorage.setItem(
        this.key,
        JSON.stringify(draft)
      );
    } catch {
      // Storage may be blocked by browser privacy settings.
    }
  },

  restore(form) {
    const draft = this.read();

    if (!draft.fields) {
      return;
    }

    Object.entries(draft.fields).forEach(
      ([name, value]) => {
        const field =
          form.elements.namedItem(name);

        if (
          !field
          || field instanceof RadioNodeList
        ) {
          return;
        }

        if (
          field.type === "checkbox"
        ) {
          field.checked = Boolean(value);
          return;
        }

        if (
          typeof value === "string"
          && (
            field.value === ""
            || field.dataset.restoreDraft === "always"
          )
        ) {
          field.value = value;
        }
      }
    );

    form
      .querySelectorAll("[data-type]")
      .forEach((field) => {
        field.dispatchEvent(
          new Event(
            "input",
            { bubbles: true }
          )
        );
      });
  },

  initialize() {
    if (this.isEdit()) {
      return;
    }

    if (this.isExplicitNew()) {
      this.clear();
      return;
    }

    const form = this.form();

    if (!form) {
      return;
    }

    this.restore(form);

    const save = () => this.write(form);

    form.addEventListener("input", save);
    form.addEventListener("change", save);
    window.addEventListener(
      "beforeunload",
      save
    );
  },

  clear() {
    try {
      localStorage.removeItem(this.key);
    } catch {}
  },
};

const Wizard = {
  initialize() {
    document
      .querySelectorAll("[data-type]")
      .forEach((input) => {
        const type =
          input.dataset.type;

        if (
          typeof InputTypes[type]
          === "function"
        ) {
          InputTypes[type](input);
        }
      });

    const employmentStatus =
      document.querySelector(
        "#employment_status"
      );

    const attainment =
      document.querySelector(
        "#highest_educational_attainment"
      );

    employmentStatus?.addEventListener(
      "change",
      () => ConditionalFields.livelihood()
    );

    attainment?.addEventListener(
      "change",
      () => ConditionalFields.education()
    );

    ConditionalFields.livelihood();
    ConditionalFields.education();

    WizardAutoSave.initialize();
  },
};

document.addEventListener(
  "DOMContentLoaded",
  () => Wizard.initialize()
);

window.AcesWizard = {
  clearDraft: () =>
    WizardAutoSave.clear(),
};
