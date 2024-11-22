// src/hooks/useCart.js
import { useState, useEffect } from 'react';
import { v4 as uuidv4 } from 'uuid';

const useCart = (updateProducts) => {
    //génerer un id de panier unique aléatoire uuid
    const cartId = localStorage.getItem('cartId');
    if (!cartId) {
        localStorage.setItem('cartId', uuidv4());
    }
  const [cart, setCart] = useState(() => {
    const savedCart = localStorage.getItem('cart');
    return savedCart ? JSON.parse(savedCart) : [];
  });

  useEffect(() => {
    localStorage.setItem('cart', JSON.stringify(cart));
    
  }, [cart]);

  const addToCart = (item, quantity) => {
    //On check si il reste de la quantité demandée en stock
    if (item.stock < quantity) {
      //Si non, on ne peut pas ajouter le produit au panier donc on affiche un message d'erreur sur la product card
      item.error = true;
      item.errorMessage = "Stock insuffisant";
      updateProducts(item);

    }

    //Réservé le stock du produit
    let productQuantityAvailable = false;
    fetch(`/reserve/${item.id}`, {
      method: 'POST',
      body: JSON.stringify({cartId: cartId, quantity: quantity})
    }).then(response => response.json()).then(data => {
      if(data.success){
        if (cart.find(p => p.id === item.id)) {
          setCart(cart.map(p => p.id === item.id ? {...p, quantitySelected: parseInt(p.quantitySelected) + parseInt(quantity)} : p));
        } else {
          setCart([...cart, {...item, quantitySelected: quantity}]);
        }
        
        item.stock -= quantity;
        updateProducts(item);
      }
      else{
        item.error = true;
        item.errorMessage = "Stock insuffisant";
        updateProducts(item);
      }
    });
 
  };

  const removeFromCart = (id) => {
    //Si la quantité est 1, supprimer le produit, sinon diminuer la quantité
    if (cart.find(p => p.id === id).quantitySelected === 1) {
      setCart(cart.filter(item => item.id !== id));
    } else {
      setCart(cart.map(p => p.id === id ? {...p, quantitySelected: parseInt(p.quantitySelected) - 1} : p));
    }

    //Annuler la réservation du produit
    fetch(`/unreserve/${id}`, {
      method: 'POST',
      body: JSON.stringify({cartId: cartId})
    });
  };

  return { cart, addToCart, removeFromCart };
};

export default useCart;
