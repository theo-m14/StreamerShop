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

    return (<div className="product-card">
        <img src={`/images/products/${product.imageName}`} alt={product.title} />
        <div className="product-name">{product.title}</div>
        <div className="product-price">{product.price}</div>
        {product.error && <div className="product-error">{product.errorMessage}</div>}
        {product.stock > 0 && <input type="number" min="1" defaultValue="1" max={product.stock} onChange={handleQuantityChange} />}
        {!product.error && product.stock > 0 && <button onClick={() => {addToCart(product, quantity)}}>Ajouter au panier</button>}
        {product.stock === 0 && <button className="product-sold-out">Rupture de stock</button>}
    </div>);
}
