document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('dyslexia-toggle');
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            document.body.classList.toggle('dyslexia-font');
            document.body.classList.toggle('dyslexia-background');
        });
    }
});