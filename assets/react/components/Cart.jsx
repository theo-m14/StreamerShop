import React from 'react';
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

    const handleCheckout = () => {
        setCartisValidate(true);
    }

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
            <button onClick={handleCheckout}>Passer au paiement</button>
        </div>
    );
}
