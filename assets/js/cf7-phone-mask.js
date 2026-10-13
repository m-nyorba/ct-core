(function () {
  "use strict";

  function normalizeDigits(value) {
    var digits = value.replace(/\D/g, "");
    var prefix = phoneMaskSettings.pattern.split("X")[0] || "";
    var prefixDigits = prefix.replace(/\D/g, "");

    if (prefixDigits && digits.indexOf(prefixDigits) === 0) {
      digits = digits.slice(prefixDigits.length);
    }

    return digits.slice(0, phoneMaskSettings.digits);
  }

  function formatPhoneNumber(event) {
    var input = event.target;
    var value = normalizeDigits(input.value);

    if (value.length === 0) {
      input.value = "";
      return;
    }

    var formattedValue = "";
    var index = 0;
    var pattern = phoneMaskSettings.pattern;

    for (var i = 0; i < pattern.length; i++) {
      var ch = pattern.charAt(i);
      if (ch === "X") {
        if (index < value.length) {
          formattedValue += value.charAt(index++);
        } else {
          break;
        }
      } else {
        formattedValue += ch;
      }
    }

    formattedValue = formattedValue.replace(/[^0-9]+$/g, "");
    input.value = formattedValue;
  }

  function getPhoneDigits(value) {
    return normalizeDigits(value);
  }

  function clearPhoneError(input) {
    input.classList.remove("wpcf7-not-valid");
    input.removeAttribute("aria-invalid");
    var wrapper = input.closest(".wpcf7-form-control-wrap");
    if (wrapper) {
      var tip = wrapper.querySelector(".wpcf7-not-valid-tip");
      if (tip) {
        tip.remove();
      }
    }
  }

  function setPhoneError(input, message) {
    input.classList.add("wpcf7-not-valid");
    input.setAttribute("aria-invalid", "true");

    var wrapper = input.closest(".wpcf7-form-control-wrap");
    if (wrapper) {
      clearPhoneError(input);
      var tip = document.createElement("span");
      tip.className = "wpcf7-not-valid-tip";
      tip.textContent = message;
      wrapper.appendChild(tip);
    }
  }

  function validatePhoneField(input) {
    var digits = getPhoneDigits(input.value);
    if (digits.length < phoneMaskSettings.digits) {
      if (digits.length > 0) {
        setPhoneError(input, phoneMaskSettings.messages.invalid_phone);
      }
      return false;
    }

    clearPhoneError(input);
    return true;
  }

  function initPhoneMask() {
    var inputs = document.querySelectorAll(".phone-mask");

    inputs.forEach(function (input) {
      input.setAttribute("placeholder", phoneMaskSettings.pattern);

      if (input.dataset.maskInitialized) {
        return;
      }

      input.addEventListener("input", function (event) {
        formatPhoneNumber(event);
        clearPhoneError(input);
      });

      input.addEventListener("blur", function () {
        validatePhoneField(input);
      });

      input.dataset.maskInitialized = "true";
    });
  }

  var phoneMaskSettings = {
    pattern: "+38 (XXX) XXX-XX-XX",
    digits: 10,
  };

  if (typeof window.ctCoreCf7PhoneMask !== "undefined") {
    phoneMaskSettings = window.ctCoreCf7PhoneMask;
  }

  phoneMaskSettings.messages = phoneMaskSettings.messages || {
    invalid_phone: "Please enter a complete phone number.",
  };

  document.addEventListener("DOMContentLoaded", initPhoneMask, false);
  document.addEventListener("wpcf7submit", initPhoneMask, false);
  document.addEventListener("wpcf7init", initPhoneMask, false);
})();
