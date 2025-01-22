import React, { useState } from 'react';

export default function ({product, addToCart}) {
    const [quantity, setQuantity] = useState(1);

    const handleQuantityChange = (e) => {
        if(e.target.value > product.stock){
            setQuantity(product.stock);
        }else{
            setQuantity(e.target.value);
        }
    }

    return (
        <div className="card">
            <div className="card-image">
                <figure className="image is-square">
                    <img src={`/images/products/${product.imageName}`} alt={product.title} />
                </figure>
            </div>
            <div className="card-content">
                <div className="product-name title is-4">{product.title}</div>
                <div className="product-price subtitle is-5">{product.price} €</div>
                <div className="product-stock subtitle is-7">{product.stock} en stock</div>
                {product.error && <div className="product-error subtitle is-6">{product.errorMessage}</div>}
                {product.stock === 0 && <button className="product-sold-out">Rupture de stock</button>}
                    {/* {product.stock > 0 && <input type="number" min="1" defaultValue="1" max={product.stock} onChange={handleQuantityChange} />} */}
                <div className="buttons is-centered">
                    {!product.error && product.stock > 0 && <button className="button is-primary" onClick={() => {addToCart(product, quantity)}}>Ajouter au panier</button>}
                </div>
            </div>
        </div>
    );
}

