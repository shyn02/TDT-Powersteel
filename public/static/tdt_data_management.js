document.addEventListener("DOMContentLoaded", function () {
    var select = document.getElementById("tdtOlderThanSelect");
    var customInput = document.getElementById("tdtCustomDaysInput");

    if (!select || !customInput) return;

    function toggleCustomInput() {
        if (select.value === "custom") {
            customInput.style.display = "";
            customInput.required = true;
        } else {
            customInput.style.display = "none";
            customInput.required = false;
            customInput.value = "";
        }
    }

    select.addEventListener("change", toggleCustomInput);
    toggleCustomInput();
});
