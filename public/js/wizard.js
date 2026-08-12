console.log("wizard.js loaded");

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
      "de",
      "del",
      "dela",
      "de la",
      "la",
      "van",
      "von",
      "bin",
      "ibn",
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

  formatMoney(value) {
    value = value.replace(/,/g, "");

    if (!value) {
      return "";
    }

    let [whole, decimal] = value.split(".");

    whole = whole.replace(/\D/g, "");
    decimal = decimal ? decimal.replace(/\D/g, "").substring(0, 2) : "";

    if (!whole) {
      whole = "0";
    }

    whole = Number(whole).toLocaleString("en-US");

    if (value.includes(".")) {
      return `${whole}.${decimal}`;
    }

    return whole;
  },

  sanitizeMoney(value) {
    value = value.replace(/,/g, "");
    value = value.replace(/[^\d.]/g, "");

    const parts = value.split(".");

    if (parts.length > 2) {
      value = parts.shift() + "." + parts.join("");
    }

    if (value.includes(".")) {
      const [whole, decimal] = value.split(".");

      value = whole + "." + decimal.substring(0, 2);
    }

    return value;
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

  phone(input) {
    const max = Number(input.dataset.maxLength ?? 11);

    Helpers.onInput(input, (value) => {
      value = Helpers.digits(value);
      value = Helpers.limit(value, max);

      return value;
    });

    input.addEventListener("blur", () => {
      if (input.value && !/^09\d{9}$/.test(input.value)) {
        input.setCustomValidity("Enter a valid Philippine mobile number.");
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
      value = Helpers.digits(value);

      return Helpers.limit(value, max);
    });
  },

  money(input) {
    input.addEventListener("input", () => {
      let value = input.value.replace(/[^\d.]/g, "");

      const parts = value.split(".");

      let whole = parts.shift() ?? "";
      let decimal = parts.join("");

      whole = whole.replace(/^0+(?=\d)/, "");

      if (decimal.length > 2) {
        decimal = decimal.substring(0, 2);
      }

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

      if (isNaN(number)) {
        return "";
      }

      number = Math.min(100, Math.max(0, number));

      return number.toString();
    });
  },

  birthdate(input) {
    input.max = new Date().toISOString().split("T")[0];
  },

  uppercase(input) {
    Helpers.onInput(input, (value) => {
      return value.toUpperCase();
    });
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
  },
};

document.addEventListener("DOMContentLoaded", () => Wizard.initialize());
