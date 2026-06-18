const cart =
JSON.parse(
    localStorage.getItem("cart")
) || [];

const summary =
document.getElementById(
    "order-summary"
);

let total = 0;

if(cart.length === 0){

    summary.innerHTML = `

    <p>

        No Products Found

    </p>

    `;

}else{

    cart.forEach(item=>{

        total +=
        item.price *
        item.quantity;

        summary.innerHTML += `

        <div class="d-flex justify-content-between mb-2">

            <span>

                ${item.name}
                x ${item.quantity}

            </span>

            <strong>

                $${item.price * item.quantity}

            </strong>

        </div>

        `;

    });

    summary.innerHTML += `

    <hr>

    <h4>

        Total: $${total}

    </h4>

    `;

}

document
.getElementById(
    "checkoutForm"
)
.addEventListener(
    "submit",
    function(e){

        e.preventDefault();

        alert(
            "Order Placed Successfully"
        );

        localStorage.removeItem(
            "cart"
        );

        window.location.href =
        "home.html";

    }
);