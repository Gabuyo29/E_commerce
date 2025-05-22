function validatePassword() {
            const password = document.getElementById('password').value;
            const indicators = {
                uppercase: document.getElementById('uppercase'),
                lowercase: document.getElementById('lowercase'),
                number: document.getElementById('number'),
                special: document.getElementById('special'),
                length: document.getElementById('length')
            };

            // Validation patterns
            const validations = {
                uppercase: /[A-Z]/,
                lowercase: /[a-z]/,
                number: /\d/,
                special: /[\W_]/,
                length: /.{8,}/
            };

            // Update indicators
            for (const key in validations) {
                if (validations[key].test(password)) {
                    indicators[key].classList.add('valid');
                    indicators[key].classList.remove('invalid');
                } else {
                    indicators[key].classList.add('invalid');
                    indicators[key].classList.remove('valid');
                }
            }

            // Check if all validations pass
            const allValid = Object.values(validations).every((regex) => regex.test(password));
            return allValid;
        }