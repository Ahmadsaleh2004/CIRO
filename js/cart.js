let cart =
JSON.parse(
    localStorage.getItem("cart")
) || [];

const container =
document.getElementById(
    "cart-container"
);

renderCart();

function renderCart(){

    if(cart.length === 0){

        container.innerHTML = `

        <div class="text-center py-5">

            <h2>
                🛒 Cart Is Empty
            </h2>

            <p class="mt-3">
                Add Products To Continue Shopping
            </p>

            <a
                href="products.html"
                class="btn btn-primary"
            >
                Browse Products
            </a>

        </div>

        `;

        return;
    }

    let total = 0;

    container.innerHTML = "";

    cart.forEach(item=>{

        total +=
        item.price *
        item.quantity;

        container.innerHTML += `

        <div class="card mb-4 p-3">

            <div class="row align-items-center">

                <div class="col-md-2">

                    <a
                        href="product-details.html?id=${item.id}"
                    >

                        <img
                            src="${item.image}"
                            class="img-fluid rounded"
                            alt="${item.name}"
                        >

                    </a>

                </div>

                <div class="col-md-3">

                 <a
    href="product-details.html?id=${item.id}"
    class="text-decoration-none product-link"
>

    <h5>
        ${item.name}
    </h5>

</a>
                </div>

                <div class="col-md-2">

                    <strong>
                        $${item.price}
                    </strong>

                </div>

                <div class="col-md-2">

                    <div
                        class="d-flex gap-2"
                    >

                        <button
                            class="btn btn-outline-secondary minus"
                            data-id="${item.id}"
                        >
                            -
                        </button>

                        <span>
                            ${item.quantity}
                        </span>

                        <button
                            class="btn btn-outline-secondary plus"
                            data-id="${item.id}"
                        >
                            +
                        </button>

                    </div>

                </div>

                <div class="col-md-2">

                    <strong>

                        $${item.price * item.quantity}

                    </strong>

                </div>

                <div class="col-md-1">

                    <button
                        class="btn btn-danger remove-item"
                        data-id="${item.id}"
                    >

                        ✖

                    </button>

                </div>

            </div>

        </div>

        `;

    });

    container.innerHTML += `

    <div class="card p-4">

        <h3>

            Total: $${total}

        </h3>

        <a
            href="checkout.html"
            class="btn btn-success mt-3"
        >

            Proceed To Checkout

        </a>

    </div>

    `;

    activateButtons();

}
function activateButtons(){

    document
    .querySelectorAll(".plus")
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            parseInt(
                btn.dataset.id
            );

            const item =
            cart.find(
                p => p.id === id
            );

            item.quantity++;

            saveCart();

        };

    });

    document
    .querySelectorAll(".minus")
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            parseInt(
                btn.dataset.id
            );

            const item =
            cart.find(
                p => p.id === id
            );

            if(
                item.quantity > 1
            ){

                item.quantity--;

            }

            saveCart();

        };

    });

    document
    .querySelectorAll(".remove-item")
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            parseInt(
                btn.dataset.id
            );

            cart =
            cart.filter(
                item =>
                item.id !== id
            );

            saveCart();

        };

    });

}

function saveCart(){

    localStorage.setItem(
        "cart",
        JSON.stringify(cart)
    );

    if(
        typeof updateCounters ===
        "function"
    ){

        updateCounters();

    }

    renderCart();

}