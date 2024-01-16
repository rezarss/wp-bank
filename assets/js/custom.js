function xAlert(msg, type, textColor, time = 5000) {
    $("#x-alert").addClass("x-bg-" + type + " x-text-" + textColor);
    $("#x-alert").append(msg)
    $("#x-alert").show()
    setTimeout( () => {
        $("#x-alert").removeClass("x-bg-" + type + " x-text-" + textColor);
        $("#x-alert").html('');
        $("#x-alert").hide()
    }, time)
}