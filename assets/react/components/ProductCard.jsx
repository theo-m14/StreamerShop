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
        <div className="card is-flex is-flex-direction-column" style={{height: '100%'}}>
            <div className="card-image product-image">
                <figure className="image is-fullwidth">
                    <img src={`/images/products/${product.imageName}`} alt={product.title} />
                </figure>
            </div>
            <div className="card-content is-flex is-flex-direction-column" style={{height: '100%', justifyContent: 'space-between'}}>
                <div>
                    <div className="product-name title is-4">{product.title}</div>
                    <div className="product-price subtitle is-5">{product.price} €</div>
                    <div className="product-stock subtitle is-7 mb-6">{product.stock} en stock</div>
                    {product.error && <div className="product-error subtitle is-6">{product.errorMessage}</div>}
                    {product.stock === 0 && <button className="product-sold-out">Rupture de stock</button>}
                </div>
                <div className="buttons is-centered mt-auto">
                    {!product.error && product.stock > 0 && 
                        <button className="button is-primary" onClick={() => {addToCart(product, quantity)}}>
                            Ajouter au panier
                        </button>
                    }
                </div>
            </div>
        </div>
    );
}

