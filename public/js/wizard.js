/**
 * ==========================================================
 * ACES Wizard Input Engine
 * ==========================================================
 */

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
        if (particles.includes(word)) return word;
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

  formatMoney(value) {
    value = value.replace(/,/g, "");

    if (!value) return "";

    let [whole, decimal] = value.split(".");
    whole = whole.replace(/\D/g, "");
    decimal = decimal ? decimal.replace(/\D/g, "").substring(0, 2) : "";

    if (!whole) whole = "0";

    whole = Number(whole).toLocaleString("en-US");

    return value.includes(".") ? `${whole}.${decimal}` : whole;
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
    Helpers.onInput(input, (value) => {
      value = Helpers.trim(value)
        .replace(/[^A-Za-z0-9.\-\s]/g, "")
        .replace(/\s+/g, " ");

      const normalized = value.toLowerCase();

      if (["jr", "jr."].includes(normalized)) return "Jr.";
      if (["sr", "sr."].includes(normalized)) return "Sr.";

      // Roman numeral suffixes are conventionally uppercase.
      if (/^(?:i{1,3}|iv|vi|v|ix|xi{0,3})$/i.test(normalized)) {
        return normalized.toUpperCase();
      }

      return value;
    });
  },

  houseNumber(input) {
    Helpers.onInput(input, (value) => {
      value = value
        .replace(/[^A-Za-z0-9.,#\-\/\s]/g, "")
        .replace(/\s+/g, " ")
        .trimStart();

      return value
        .split(" ")
        .map((word) => {
          const lower = word.toLowerCase();
          if (lower === "block" || lower === "lot") {
            return lower.charAt(0).toUpperCase() + lower.slice(1);
          }
          return word;
        })
        .join(" ");
    });
  },

  phone(input) {
    const max = Number(input.dataset.maxLength ?? 11);

    Helpers.onInput(input, (value) => {
      return Helpers.limit(Helpers.digits(value), max);
    });

    input.addEventListener("blur", () => {
      if (input.value && !/^09\d{9}$/.test(input.value)) {
        input.setCustomValidity("Enter a valid Philippine mobile number.");
      } else {
        input.setCustomValidity("");
      }
    });
  },

  telephone(input) {
    const max = Number(input.dataset.maxLength ?? 11);

    const format = (value) => {
      const digits = Helpers.limit(Helpers.digits(value), max);

      if (digits.length <= 7) {
        if (digits.length <= 3) return digits;
        return `${digits.slice(0, 3)}-${digits.slice(3)}`;
      }

      if (digits.startsWith("02") && digits.length <= 10) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
      }

      if (digits.length >= 10) {
        return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}${digits.slice(10)}`;
      }

      return digits;
    };

    input.addEventListener("input", () => {
      input.value = format(input.value);
      input.setCustomValidity("");
    });

    input.addEventListener("blur", () => {
      const digits = Helpers.digits(input.value);

      if (digits && (digits.length < 7 || digits.length > max)) {
        input.setCustomValidity("Enter a valid telephone number.");
      } else {
        input.setCustomValidity("");
      }
    });
  },

  email(input) {
    Helpers.onInput(input, (value) => {
      return value.replace(/\s+/g, "").toLowerCase();
    });
  },

  zip(input) {
    const max = Number(input.dataset.maxLength ?? 4);

    Helpers.onInput(input, (value) => {
      return Helpers.limit(Helpers.digits(value), max);
    });
  },

  money(input) {
    input.addEventListener("input", () => {
      let value = input.value.replace(/[^\d.]/g, "");
      const parts = value.split(".");

      let whole = parts.shift() ?? "";
      let decimal = parts.join("").substring(0, 2);

      whole = whole.replace(/^0+(?=\d)/, "");
      whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ",");

      input.value = decimal !== "" ? `${whole}.${decimal}` : whole;
    });
  },

  percentage(input) {
    Helpers.onInput(input, (value) => {
      value = value.replace(/[^\d.]/g, "");
      const parts = value.split(".");

      if (parts.length > 2) {
        value = parts.shift() + "." + parts.join("");
      }

      let number = parseFloat(value);

      if (isNaN(number)) return "";

      number = Math.min(100, Math.max(0, number));
      return number.toString();
    });
  },

  birthdate(input) {
    input.max = new Date().toISOString().split("T")[0];
  },
};

const WizardAutoSave = {
  key: "aces:member-registration:draft",

  isNewRegistration() {
    return new URLSearchParams(window.location.search).get("new") === "1";
  },

  active() {
    return !new URLSearchParams(window.location.search).has("edit");
  },

  form() {
    return Array.from(
      document.querySelectorAll(".wizard__body form")
    ).find((candidate) => {
      if (candidate.action.includes("/members/beneficiaries")) return false;

      return candidate.querySelector(
        "input[name], select[name], textarea[name]"
      );
    });
  },

  read() {
    try {
      const raw = localStorage.getItem(this.key);
      return raw ? JSON.parse(raw) : {};
    } catch {
      return {};
    }
  },

  write(form) {
    const data = {};

    form.querySelectorAll(
      "input[name], select[name], textarea[name]"
    ).forEach((field) => {
      if (field.type === "file") return;

      data[field.name] = field.type === "checkbox"
        ? field.checked
        : field.value;
    });

    try {
      localStorage.setItem(this.key, JSON.stringify(data));
    } catch {
      // Storage availability varies by browser/privacy mode.
    }
  },

  restore(form) {
    const data = this.read();

    Object.entries(data).forEach(([name, value]) => {
      const field = form.elements.namedItem(name);

      if (!field) return;

      if (field instanceof RadioNodeList) {
        return;
      }

      if (field.type === "checkbox") {
        field.checked = Boolean(value);
      } else if (
        (field.value === "" || field.dataset.autosaveRestore === "always")
        && typeof value === "string"
      ) {
        field.value = value;
      }
    });

    form.querySelectorAll("[data-type]").forEach((field) => {
      field.dispatchEvent(new Event("input", { bubbles: true }));
    });
  },

  initialize() {
    if (!this.active()) return;

    if (this.isNewRegistration()) {
      localStorage.removeItem(this.key);
      return;
    }

    const form = this.form();

    if (!form) return;

    this.restore(form);

    const save = () => this.write(form);

    form.addEventListener("input", save);
    form.addEventListener("change", save);
    window.addEventListener("beforeunload", save);
  },
};

const Wizard = {
  initialize() {
    document.querySelectorAll("[data-type]").forEach((input) => {
      const type = input.dataset.type;

      if (typeof InputTypes[type] === "function") {
        InputTypes[type](input);
      }
    });

    WizardAutoSave.initialize();
  },
};

document.addEventListener("DOMContentLoaded", () => Wizard.initialize());
