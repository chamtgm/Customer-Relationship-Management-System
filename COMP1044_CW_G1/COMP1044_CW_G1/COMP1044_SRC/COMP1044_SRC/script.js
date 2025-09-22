function changeStyle(){
    const heading = document.getElementById("heading");
    heading.style.color = "red";
    heading.style.backgroundColor = "black";
    heading.style.fontFamily = "Arial";
    heading.style.flexDirection = "row";
    function animate(){
        position += 1;
        box.style.left = position + "px";
        if (position < 500) {
            requestAnimationFrame(moveBox);
        }    
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const showSignupBtn = document.getElementById('showSignup');
    const showLoginBtn = document.getElementById('showLogin');
    const formsWrapper = document.querySelector('.forms-wrapper');

    showSignupBtn.addEventListener('click', function(e) {
        e.preventDefault();
        formsWrapper.style.transform = 'translateX(-50%)';
    });

    showLoginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        formsWrapper.style.transform = 'translateX(0)';
    });
});