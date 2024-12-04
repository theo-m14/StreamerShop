import * as React from 'react';
import {
  EmbeddedCheckoutProvider,
  EmbeddedCheckout
} from '@stripe/react-stripe-js';
import AdressSelection from './AdressSelection';
// Make sure to call `loadStripe` outside of a component’s render to avoid
// recreating the `Stripe` object on every render.
export default function ({clientSecret, stripePromise}) {
  const options = {clientSecret};
  return (
    <>
      <AdressSelection />
      {/* <EmbeddedCheckoutProvider
        stripe={stripePromise}
        options={options}
    >
        <EmbeddedCheckout />
      </EmbeddedCheckoutProvider> */}
    </>
  )
}