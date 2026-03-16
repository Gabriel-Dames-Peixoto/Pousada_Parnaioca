const inputCpf = document.getElementById('cpf');

inputCpf.addEventListener('keypress', () => {
    let inputLength = inputCpf.value.length;

    // Adiciona ponto após o 3º e 7º caracteres
    if (inputLength === 3 || inputLength === 7) {
        inputCpf.value += '.';
    } 
    // Adiciona traço após o 11º caractere
    else if (inputLength === 11) {
        inputCpf.value += '-';
    }
});
