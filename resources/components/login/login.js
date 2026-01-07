// JavaScript for login

(() => {
  const init = () => {
    const root = document.querySelector('[data-login]');
    if (!root) {
      return;
    }

    const form = root.querySelector('[data-login-form]') || root.querySelector('#login-form');
    const email = root.querySelector('[data-login-email]') || root.querySelector('[data-login-username]');
    const password = root.querySelector('[data-login-password]');
    const toggle = root.querySelector('[data-login-toggle]');
    const error = root.querySelector('[data-login-error]');
    const submit = root.querySelector('[data-login-submit]');

    if (!form || !email || !password) {
      return;
    }

  const setError = (message) => {
    if (!error) {
      return;
    }

    error.textContent = message;
    error.hidden = message.length === 0;
  };

  const setInvalid = (input, state) => {
    if (!input) {
      return;
    }

    if (state) {
      input.setAttribute('aria-invalid', 'true');
    } else {
      input.removeAttribute('aria-invalid');
    }
  };

  const validate = () => {
    let isValid = true;

    if (!email.value.trim()) {
      setInvalid(email, true);
      isValid = false;
    } else {
      setInvalid(email, false);
    }

    if (!password.value.trim()) {
      setInvalid(password, true);
      isValid = false;
    } else {
      setInvalid(password, false);
    }

    if (!isValid) {
      setError('Vul je e-mail en wachtwoord in.');
    } else {
      setError('');
    }

    return isValid;
  };

    form.addEventListener('submit', (event) => {
      if (!validate()) {
        event.preventDefault();
        return;
      }

      if (submit) {
        submit.dataset.originalText = submit.textContent || '';
        submit.textContent = 'Bezig...';
        submit.disabled = true;
      }
    });

    form.addEventListener('input', () => {
      if (error && !error.hidden) {
        setError('');
      }

      if (submit && submit.disabled) {
        submit.disabled = false;
        if (submit.dataset.originalText) {
          submit.textContent = submit.dataset.originalText;
        }
      }
    });

    if (toggle && password) {
      toggle.addEventListener('click', () => {
        const showPassword = password.type === 'password';
        password.type = showPassword ? 'text' : 'password';
        toggle.textContent = showPassword ? 'Verberg' : 'Toon';
        toggle.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
      });
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
