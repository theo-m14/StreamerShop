import * as React from 'react';
import {
  EmbeddedCheckoutProvider,
  EmbeddedCheckout
} from '@stripe/react-stripe-js';
import {loadStripe} from '@stripe/stripe-js';
import AdressSelection from './AdressSelection';
// Make sure to call `loadStripe` outside of a component’s render to avoid
// recreating the `Stripe` object on every render.
export default function ({cart}) {

  const [clientSecret, setClientSecret] = React.useState(null);

  const stripePromise = loadStripe('pk_test_51O0mx9Ddah1uLYmzf0SMrTBkSFADmF9sR63AdXlea9ZT8OOfMrzhQIBb62SVTIiTwrnbYbV8QXPJsvtooYRudZb600UHHJ3MFq');
  const options = {clientSecret,onComplete: () => {
    fetch('/createOrder', {
      method: 'POST',
      body: JSON.stringify({session_id: sessionId,cart: cart,total: total,adress: adress}),
    });
  }};
  const [adress, setAdress] = React.useState(null);

  const handleAdressRegistration = (adress) => {
    setAdress(adress);
    createCheckoutSession(adress);
  }

    const createCheckoutSession = async (adress) => {
      try {
          const response = await fetch('/checkout', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                  cart: cart.map(item => ({
                      id: item.id,
                      name: item.title,
                      price: item.price,
                      quantity: item.quantitySelected
                  })),
                  adress: adress
              })
          });

          if (!response.ok) {
              throw new Error('Erreur lors du checkout');
          }

          const checkoutSession = await response.json();

          // Gérer la réponse de Stripe ici
          setClientSecret(checkoutSession.client_secret);
      } catch (error) {
          console.error('Erreur:', error);
      }
    }
  return (
    <>
      <AdressSelection handleAdressRegistration={handleAdressRegistration} />
      {adress && clientSecret && (
        <div className="container is-max-desktop">
          <EmbeddedCheckoutProvider
            stripe={stripePromise}
            options={options}
        >
          <EmbeddedCheckout className="box mt-6 mb-6" id="embedded-checkout-stripe" />
        </EmbeddedCheckoutProvider>
        </div>
      )}
    </>
  )
}