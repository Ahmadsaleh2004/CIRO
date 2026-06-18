const params =
new URLSearchParams(
    window.location.search
);

const productId =
parseInt(
    params.get("id")
);

const product =
products.find(
    p => p.id === productId
);

const container =
document.getElementById(
    "product-details"
);

if(product){

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

    const wishlist =
    JSON.parse(
        localStorage.getItem(
            "wishlist"
        )
    ) || [];

    const isFavorite =
    wishlist.some(
        item =>
        item.id === product.id
    );

    container.innerHTML = `

    <div class="row g-5 align-items-center">

        <div class="col-lg-6">

            <div class="position-relative">

                <span class="discount-badge">
                    -${discount}%
                </span>

                <button
                    id="addWishlistBtn"
                    class="favorite-btn"
                >
                    ${isFavorite ? "❤️" : "🤍"}
                </button>

                <img
                    src="${product.image}"
                    class="img-fluid rounded shadow product-image"
                    alt="${product.name}"
                >

            </div>

        </div>

        <div class="col-lg-6">

            <h1 class="fw-bold mb-3">
                ${product.name}
            </h1>

            <div class="price-box mb-3">

                <span class="new-price">
                    $${product.price}
                </span>

                <span class="old-price">
                    $${oldPrice}
                </span>

            </div>

         <p class="mb-4">
    ${product.description}
</p>

<hr>

<div class="mb-4">

    <p>

        <strong>Brand:</strong>
        ${product.brand || "Unknown"}

    </p>

    <p>

        <strong>Category:</strong>
        ${product.category || "Electronics"}

    </p>

    <p>

        <strong>Stock:</strong>
        ${product.stock || 15}

    </p>

    <p>

        <strong>Manufacture Date:</strong>
        ${product.manufactureDate || "2025"}

    </p>

</div>
            <div class="quantity-box mb-4">

                <button
                    id="minusBtn"
                    class="btn btn-outline-secondary"
                >
                    -
                </button>

                <input
                    type="number"
                    value="1"
                    min="1"
                    id="productQty"
                    class="form-control quantity-input"
                >

                <button
                    id="plusBtn"
                    class="btn btn-outline-secondary"
                >
                    +
                </button>

            </div>

            <button
                id="addCartBtn"
                class="btn btn-success btn-lg"
            >
                🛒 Add To Cart
            </button>

        </div>

    </div>

    `;
activateDetailsButtons(
    product
);

renderRelatedProducts(
    product.id
);
}

function activateDetailsButtons(
    product
){

    const qtyInput =
    document.getElementById(
        "productQty"
    );

    document
    .getElementById(
        "plusBtn"
    )
    .onclick = ()=>{

        qtyInput.value =
        parseInt(
            qtyInput.value
        ) + 1;

    };

    document
    .getElementById(
        "minusBtn"
    )
    .onclick = ()=>{

        if(
            parseInt(
                qtyInput.value
            ) > 1
        ){

            qtyInput.value =
            parseInt(
                qtyInput.value
            ) - 1;

        }

    };

    document
    .getElementById(
        "addCartBtn"
    )
    .onclick = ()=>{

        let cart =
        JSON.parse(
            localStorage.getItem(
                "cart"
            )
        ) || [];

        const quantity =
        parseInt(
            qtyInput.value
        );

        const existing =
        cart.find(
            item =>
            item.id === product.id
        );

        if(existing){

            existing.quantity +=
            quantity;

        }else{

            cart.push({

                ...product,

                quantity

            });

        }

        localStorage.setItem(
            "cart",
            JSON.stringify(cart)
        );updateCounters();

        alert(
            "Added To Cart"
        );

    };

    document
    .getElementById(
        "addWishlistBtn"
    )
    .onclick = ()=>{

        let wishlist =
        JSON.parse(
            localStorage.getItem(
                "wishlist"
            )
        ) || [];

        const exists =
        wishlist.find(
            item =>
            item.id === product.id
        );

        const btn =
        document.getElementById(
            "addWishlistBtn"
        );

        if(exists){

            wishlist =
            wishlist.filter(
                item =>
                item.id !== product.id
            );

            localStorage.setItem(
                "wishlist",
                JSON.stringify(
                    wishlist
                )
            );updateCounters();

            btn.innerHTML =
            "🤍";

            return;

        }

        wishlist.push(
            product
        );

        localStorage.setItem(
            "wishlist",
            JSON.stringify(
                wishlist
            )
        );
updateCounters();
        btn.innerHTML =
        "❤️";

    };

}
function renderRelatedProducts(
    currentId
){

    const container =
    document.getElementById(
        "related-products"
    );

    if(!container) return;

    const related =
    products
    .filter(
        product =>
        product.id !== currentId
    )
    .slice(0,4);

    container.innerHTML = "";

    related.forEach(product=>{

        const oldPrice =
        Math.round(
            product.price * 1.25
        );

        container.innerHTML += `

        <div class="col-lg-3 col-md-6 mb-4">

            <div class="card h-100 product-card">

                <a href="product-details.html?id=${product.id}">

                    <img
                        src="${product.image}"
                        class="card-img-top product-image"
                        alt="${product.name}"
                    >

                </a>

                <div class="card-body text-center">

                    <h5>

                        ${product.name}

                    </h5>

                    <div class="price-box justify-content-center">

                        <span class="new-price">

                            $${product.price}

                        </span>

                        <span class="old-price">

                            $${oldPrice}

                        </span>

                    </div>

                </div>

            </div>

        </div>

        `;

    });

}