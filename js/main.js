document.addEventListener(
    "DOMContentLoaded",
    () => {

        applySavedTheme();

        initializeTheme();

        updateCounters();

    }
);

function applySavedTheme(){

    const themeStyle =
    document.getElementById(
        "theme-style"
    );

    if(!themeStyle) return;

    const savedTheme =
    localStorage.getItem(
        "theme"
    );

    if(savedTheme === "light"){

        themeStyle.disabled = true;

    }else{

        themeStyle.disabled = false;

    }

}

function initializeTheme(){

    const themeToggle =
    document.getElementById(
        "theme-toggle"
    );

    const themeStyle =
    document.getElementById(
        "theme-style"
    );

    if(!themeToggle || !themeStyle)
    return;

    if(themeStyle.disabled){

        themeToggle.innerHTML =
        "☀️";

    }else{

        themeToggle.innerHTML =
        "🌙";

    }

    themeToggle.onclick = () => {

        if(themeStyle.disabled){

            themeStyle.disabled =
            false;

            localStorage.setItem(
                "theme",
                "dark"
            );

            themeToggle.innerHTML =
            "🌙";

        }else{

            themeStyle.disabled =
            true;

            localStorage.setItem(
                "theme",
                "light"
            );

            themeToggle.innerHTML =
            "☀️";

        }

    };

}

function updateCounters(){

    const wishlistCount =
    document.getElementById(
        "wishlist-count"
    );

    const cartCount =
    document.getElementById(
        "cart-count"
    );

    const wishlist =
    JSON.parse(
        localStorage.getItem(
            "wishlist"
        )
    ) || [];
    

    const cart =
    JSON.parse(
        localStorage.getItem(
            "cart"
        )
    ) || [];
    

    
    if(wishlistCount){

        wishlistCount.textContent =
        wishlist.length;

    }

    if(cartCount){

        let total = 0;

        cart.forEach(item => {

            total +=
            item.quantity || 1;

        });

        cartCount.textContent =
        total;

    }

}
