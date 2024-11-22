import React from 'react';
import ProductCard from './ProductCard';

export default function ({cart, removeFromCart}) {

    return (
        <div className="cart">
            <h2>Panier</h2>
            {cart.map((product) => (
                <div className="cart-item" key={product.id}>
                    <img src={`/images/products/${product.imageName}`} alt={product.title} />
                    <div className="product-name">{product.title}</div>
                    <div className="product-price">{product.price}</div>
                    <div className="product-quantity">{product.quantitySelected}</div>
                    {/* Si la quantité est 1, supprimer le produit, sinon diminuer la quantité avec un bouton - */}
                    {product.quantitySelected === 1 && <button onClick={() => removeFromCart(product.id)}>Supprimer</button>}
                    {product.quantitySelected > 1 && <button onClick={() => removeFromCart(product.id, 1)}>-</button>}
                </div>
            ))}
            <div className="cart-total">Total : {cart.reduce((acc, product) => (acc + product.price * product.quantitySelected), 0).toFixed(2)} €</div>
        </div>
    );
}
