import React, { useState } from 'react';
import ProductCard from './ProductCard';

export default function ({cart, removeFromCart, setCartisValidate}) {

    // const handleCheckout = async () => {
    //     try {
    //         const response = await fetch('/checkout', {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json',
    //             },
    //             body: JSON.stringify({
    //                 cart: cart.map(item => ({
    //                     id: item.id,
    //                     name: item.title,
    //                     price: item.price,
    //                     quantity: item.quantitySelected
    //                 }))
    //             })
    //         });

    //         if (!response.ok) {
    //             throw new Error('Erreur lors du checkout');
    //         }

    //         const checkoutSession = await response.json();

    //         // Gérer la réponse de Stripe ici
    //         setClientSecret(checkoutSession.client_secret);
    //         setSessionId(checkoutSession.session_id);
    //     } catch (error) {
    //         console.error('Erreur:', error);
    //     }
    // }

    const [isCartOpen, setIsCartOpen] = useState(false);

    const handleCheckout = () => {
        setCartisValidate(true);
    }

    return (
        <div className="cart">
            <div className="cart-icon" onClick={() => setIsCartOpen(!isCartOpen)}>
                {/* Un span fixe avec le nombre de produit dans le panier */}
                <span className="cart-count has-text-centered has-text-white has-background-danger">{cart.reduce((acc, product) => acc + product.quantitySelected, 0)}</span>
                <button className="button is-primary is-rounded is-medium is-centered">
                    <span className="icon">
                        <i className="fas fa-shopping-cart"></i>
                    </span>
                </button> 
            </div>
            <div className={`modal ${isCartOpen ? 'is-active' : ''}`}>
                <div className="modal-background" onClick={() => setIsCartOpen(false)}></div>
                <div className="modal-content">
                    <div className="box">
                        <h2 className="title is-4">Mon panier</h2>
                        {cart.map((product) => (
                            <div className="cart-item columns" key={product.id}>
                                <div className="column">
                                    <img src={`/images/products/${product.imageName}`} alt={product.title} />
                                </div>
                                <div className="column">
                                    <div className="product-name">{product.title}</div>
                                    <div className="product-price">{product.price}</div>
                                    <div className="product-quantity columns is-vcentered">
                                        <div className="column">Quantité : {product.quantitySelected}</div>
                                        <div className="column">
                                        {product.quantitySelected > 1 && <button className="button is-danger is-small" onClick={() => removeFromCart(product.id, 1)}>
                                            <span className="icon">
                                                <i className="fas fa-minus"></i>
                                            </span>
                                        </button>}
                                        </div>
                                    </div>
                                            {/* Si la quantité est 1, supprimer le produit, sinon diminuer la quantité avec un bouton - */}
                                {product.quantitySelected === 1 && <button className="button is-danger" onClick={() => removeFromCart(product.id)}>Supprimer</button>}
                                
                                </div>
                            </div>
                        ))}
                        <div className="cart-total is-size-5 has-text-white mb-4">Total : {cart.reduce((acc, product) => (acc + product.price * product.quantitySelected), 0).toFixed(2)} €</div>
                        <button className="button is-primary" onClick={handleCheckout}>Passer au paiement</button>
                    </div>
                </div>
                <button className="modal-close is-large" aria-label="close" onClick={() => setIsCartOpen(false)}></button>
            </div>
        </div>
    );
}

