const products = [
{
    id:1,
    name:"Airpods",
    price:120,
    image:"../images/airpods.jpg",
    description:"Wireless Apple Airpods with premium sound quality."
},
{
    id:2,
    name:"Airpods Pro",
    price:180,
    image:"../images/airpods pro.jpg",
    description:"Airpods Pro with active noise cancellation."
},
{
    id:3,
    name:"Apple Watch",
    price:350,
    image:"../images/apple watch.jpg",
    description:"Modern smartwatch with fitness tracking."
},
{
    id:4,
    name:"Camera",
    price:700,
    image:"../images/camera.jpg",
    description:"Professional digital camera."
},
{
    id:5,
    name:"Headphones",
    price:90,
    image:"../images/headphones.jpg",
    description:"Comfortable headphones with high quality sound."
},
{
    id:6,
    name:"iPad",
    price:800,
    image:"../images/ipad.jpg",
    description:"Powerful tablet for work and entertainment."
},
{
    id:7,
    name:"iPhone 10 Pro",
    price:900,
    image:"../images/iphon10 pro.jpg",
    description:"Premium smartphone with great performance."
},
{
    id:8,
    name:"iPhone 11 Pro",
    price:1100,
    image:"../images/iphon11 pro.jpg",
    description:"Advanced smartphone with excellent camera."
},
{
    id:9,
    name:"MacBook",
    price:1800,
    image:"../images/macbook.jpg",
    description:"High-performance laptop for professionals."
},
{
    id:10,
    name:"Nintendo Switch Lite",
    price:300,
    image:"../images/nintendo switch lite.jpg",
    description:"Portable gaming console."
},
{
    id:11,
    name:"PS4 Controller",
    price:70,
    image:"../images/ps4 controller.jpg",
    description:"Wireless PS4 controller."
},
{
    id:12,
    name:"PS4",
    price:500,
    image:"../images/ps4.jpg",
    description:"PlayStation 4 gaming console."
},
{
    id:13,
    name:"Smart Watch",
    price:150,
    image:"../images/smart watch.jpg",
    description:"Smart watch with health monitoring."
}
];

let filteredProducts = [...products];
function renderProducts(data){

    const container =
    document.getElementById(
        "products-container"
    );

    if(!container) return;

    const wishlist =
    JSON.parse(
        localStorage.getItem("wishlist")
    ) || [];

    container.innerHTML = "";

    data.forEach(product=>{

        const oldPrice =
        Math.round(product.price * 1.25);

        const discount =
        Math.round(
            ((oldPrice - product.price)
            / oldPrice) * 100
        );

        const isFavorite =
        wishlist.some(
            item => item.id === product.id
        );

        container.innerHTML += `

        <div class="col-lg-4 col-md-6 mb-4">

            <div class="card product-card h-100 shadow">

                <span class="discount-badge">
                    -${discount}%
                </span>

                <button
                    class="favorite-btn add-wishlist"
                    data-id="${product.id}"
                >
                    ${isFavorite ? "❤️" : "🤍"}
                </button>

                <a href="product-details.html?id=${product.id}">

                    <img
                        src="${product.image}"
                        class="card-img-top product-image"
                        alt="${product.name}"
                    >

                </a>

                <div class="card-body">

                    <h5 class="fw-bold">
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

                    <div class="quantity-box mb-3">

                        <button
                            class="btn btn-outline-secondary quantity-minus"
                            data-id="${product.id}"
                        >
                            -
                        </button>

                        <input
                            type="number"
                            value="1"
                            min="1"
                            id="qty-${product.id}"
                            class="form-control quantity-input"
                        >

                        <button
                            class="btn btn-outline-secondary quantity-plus"
                            data-id="${product.id}"
                        >
                            +
                        </button>

                    </div>

                    <div class="d-grid">

                        <button
                            class="btn btn-success add-cart"
                            data-id="${product.id}"
                        >
                            🛒 Add To Cart
                        </button>

                    </div>

                </div>

            </div>

        </div>

        `;
    });

    activateButtons();
}
function activateButtons(){

    document
    .querySelectorAll(".quantity-plus")
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            btn.dataset.id;

            const input =
            document.getElementById(
                `qty-${id}`
            );

            input.value =
            parseInt(input.value) + 1;

        };

    });

    document
    .querySelectorAll(".quantity-minus")
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            btn.dataset.id;

            const input =
            document.getElementById(
                `qty-${id}`
            );

            if(
                parseInt(input.value) > 1
            ){

                input.value =
                parseInt(input.value) - 1;

            }

        };

    });

    document
    .querySelectorAll(".add-cart")
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            parseInt(
                btn.dataset.id
            );

            const qty =
            parseInt(
                document.getElementById(
                    `qty-${id}`
                ).value
            );

            let cart =
            JSON.parse(
                localStorage.getItem(
                    "cart"
                )
            ) || [];
updateCounters();
            const product =
            products.find(
                p => p.id === id
            );

            const existing =
            cart.find(
                item => item.id === id
            );

            if(existing){

                existing.quantity += qty;

            }else{

                cart.push({

                    ...product,

                    quantity: qty

                });

            }

            localStorage.setItem(
                "cart",
                JSON.stringify(cart)
            );
updateCounters();
            alert(
                "Added To Cart"
            );

        };

    });

    document
    .querySelectorAll(".add-wishlist")
    .forEach(btn=>{

        btn.onclick = ()=>{

            const id =
            parseInt(btn.dataset.id);

            let wishlist =
            JSON.parse(
                localStorage.getItem(
                    "wishlist"
                )
                
            ) || [];
updateCounters();
            const exists =
            wishlist.find(
                item => item.id === id
            );

            if(exists){

                wishlist =
                wishlist.filter(
                    item => item.id !== id
                );

                localStorage.setItem(
                    "wishlist",
                    JSON.stringify(wishlist)
                );
updateCounters();
                btn.innerHTML = "🤍";

                return;

            }

            const product =
            products.find(
                p => p.id === id
            );

            wishlist.push(product);

            localStorage.setItem(
                "wishlist",
                JSON.stringify(wishlist)
            );
            updateCounters();

            btn.innerHTML = "❤️";

        };

    });

}

document.addEventListener(
    "DOMContentLoaded",
    ()=>{

        renderProducts(products);

        const search =
        document.getElementById(
            "search"
        );

        if(search){

            search.addEventListener(
                "keyup",
                ()=>{

                    const value =
                    search.value
                    .toLowerCase();

                    filteredProducts =
                    products.filter(
                        product =>
                        product.name
                        .toLowerCase()
                        .includes(value)
                    );

                    renderProducts(
                        filteredProducts
                    );

                }
            );

        }

        const sort =
        document.getElementById(
            "sort"
        );

        if(sort){

            sort.addEventListener(
                "change",
                ()=>{

                    let data =
                    [...filteredProducts];

                    switch(sort.value){

                        case "az":

                            data.sort(
                                (a,b)=>
                                a.name.localeCompare(
                                    b.name
                                )
                            );

                            break;

                        case "za":

                            data.sort(
                                (a,b)=>
                                b.name.localeCompare(
                                    a.name
                                )
                            );

                            break;

                        case "low":

                            data.sort(
                                (a,b)=>
                                a.price - b.price
                            );

                            break;

                        case "high":

                            data.sort(
                                (a,b)=>
                                b.price - a.price
                            );

                            break;

                    }

                    renderProducts(data);

                }
            );

        }

        const reset =
        document.getElementById(
            "reset"
        );

        if(reset){

            reset.addEventListener(
                "click",
                ()=>{

                    document
                    .getElementById(
                        "search"
                    ).value = "";

                    document
                    .getElementById(
                        "sort"
                    ).value = "";

                    filteredProducts =
                    [...products];

                    renderProducts(
                        products
                    );

                }
            );

        }

    }
);