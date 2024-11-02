// public/assets/js/scripts.js //Puedes agregar scripts JavaScript personalizados aquí. Por ejemplo, para mejorar la interacción de los formularios o agregar funcionalidades dinámicas.

// Ejemplo: Validación de formularios
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');

    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Ejemplo: Validar que los campos no estén vacíos
            const inputs = form.querySelectorAll('input, select');
            let valid = true;

            inputs.forEach(function(input) {
                if (input.hasAttribute('required') && !input.value.trim()) {
                    valid = false;
                    input.style.borderColor = 'red';
                } else {
                    input.style.borderColor = '#ccc';
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos obligatorios.');
            }
        });
    });
});
