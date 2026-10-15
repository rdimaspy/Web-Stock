if (window.lucide) {
    window.lucide.createIcons();
}

// Language Dropdown
const languageButton = document.getElementById("languageButton");
const languageMenu = document.getElementById("languageMenu");

if (languageButton && languageMenu) {

    languageButton.addEventListener("click", function () {

        if (languageMenu.style.display === "block") {
            languageMenu.style.display = "none";
        } else {
            languageMenu.style.display = "block";
        }

    });

}

function changeLanguage(language) {
    const elements = document.querySelectorAll("[data-id]");

    elements.forEach(function(element){
        if(language === "id") {
            element.innerHTML = element.getAttribute("data-id");
        }
        else {
            element.innerHTML = element.getAttribute("data-en");
        }
    });

    // Ubah placeholder input
    const inputs = document.querySelectorAll("[data-id-placeholder]");
    inputs.forEach(function(input){
        if(language === "id"){
            input.placeholder = input.getAttribute("data-id-placeholder");
        } else {
            input.placeholder = input.getAttribute("data-en-placeholder");
        }
    });

    // Simpan pilihan bahasa
    localStorage.setItem("language", language);

    // Reset active style
    const indonesia = document.getElementById("langIndonesia");
    const english = document.getElementById("langEnglish");

    if(indonesia && english){
        indonesia.style.background = "none";
        english.style.background = "none";

        if(language === "id"){
            indonesia.style.background = "#F5EEE5";
        }
        else{
            english.style.background = "#F5EEE5";
        }
    }

    if(languageMenu){
        languageMenu.style.display = "none";
    }

}

// Load bahasa terakhir
document.addEventListener("DOMContentLoaded", function(){
    const savedLanguage = localStorage.getItem("language");
    if(savedLanguage){
        changeLanguage(savedLanguage);
    }
});