let wishlist =
JSON.parse(
    localStorage.getItem(
        "wishlist"
    )
) || [];

const container =
document.getElementById(
    "wishlist-container"
);

renderWishlist();

function renderWishlist(){

    if(wishlist.length === 0){

        container.innerHTML = `

        <div class="col-12">

            <div class="text-center py-5">

                <h2>
                    ❤️ Wishlist Is Empty
                </h2>

                <p class="mt-3">
                    Add Some Products To Wishlist
                </p>

                <a
                    href="products.html"
                    class="btn btn-primary"
                >
                    Browse Products
                </a>

            </div>

        </div>

        `;

        return;
    }

    container.innerHTML = "";

    wishlist.forEach(product=>{

        const oldPrice =
        Math.round(
            product.price * 1.25
        );

        const discount =
        Math.round(
            (
                (oldPrice - product.price)
                / oldPrice
            ) * 100
        );

        container.innerHTML += `

        <div class="col-lg-4 col-md-6 mb-4">

            <div class="card product-card h-100">

                <span class="discount-badge">
                    -${discount}%
                </span>

                <button
                    class="favorite-btn remove-favorite"
                    data-id="${product.id}"
                >
                    ❤️
                </button>

                <a
                    href="product-details.html?id=${product.id}"
                >

                    <img
                        src="${product.image}"
                        class="card-img-top product-image"
                        alt="${product.name}"
                    >

                </a>

                <div class="card-body">

                    <h5>
                        ${product.name}
                    </h5>

                    <div class="price-box mb-3">

                        <span class="new-price">
                            $${product.price}
                        </span>

                        <span class="old-price">
                            $${oldPrice}
                        </span>

                    </div>

                    <button
                        class="btn btn-success add-cart w-100"
                        data-id="${product.id}"
                    >
                        🛒 Add To Cart
                    </button>

                </div>

            </div>

        </div>

        `;
    });

    activateWishlistButtons();
}

function activateWishlistButtons(){

    document
    .querySelectorAll(
        ".remove-favorite"
    )
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            parseInt(
                btn.dataset.id
            );

            wishlist =
            wishlist.filter(
                item =>
                item.id !== id
            );

            localStorage.setItem(
    "wishlist",
    JSON.stringify(
        wishlist
    )
);

updateCounters();

renderWishlist();

        };

    });

    document
    .querySelectorAll(
        ".add-cart"
    )
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            parseInt(
                btn.dataset.id
            );

            let cart =
            JSON.parse(
                localStorage.getItem(
                    "cart"
                )
            ) || [];

            const product =
            wishlist.find(
                item =>
                item.id === id
            );

            const existing =
            cart.find(
                item =>
                item.id === id
            );

            if(existing){

                existing.quantity += 1;

            }else{

                cart.push({

                    ...product,

                    quantity:1

                });

            }

           localStorage.setItem(
    "cart",
    JSON.stringify(
        cart
    )
);

updateCounters();

alert(
    "Added To Cart"
);

        };

    });

}