import React, { useState } from "react";
import { useStripeConnect } from "../hooks/useStripeConnect";
import {
  ConnectAccountOnboarding,
  ConnectComponentsProvider,
} from "@stripe/react-connect-js";

export default function ({ stripeAccountId }) {
  const [accountCreatePending, setAccountCreatePending] = useState(false);
  const [onboardingExited, setOnboardingExited] = useState(false);
  const [error, setError] = useState(false);
  const [connectedAccountId, setConnectedAccountId] = useState(stripeAccountId);
  const stripeConnectInstance = useStripeConnect(connectedAccountId);

  return (
    <div className="container">
      <div className="banner">
        <h2 className="title mb-4">StreamerShop</h2>
      </div>
      <div className="content">
        {!connectedAccountId && <h3>Prêt pour le décollage</h3>}
        {connectedAccountId && !stripeConnectInstance && <h3>Ajoutez des informations pour commencer à accepter l'argent</h3>}
        {!connectedAccountId && <p>StreamerShop est une plateforme de commerce pour les streamers: rejoignez notre équipe de vendeurs pour aider les gens à acheter plus rapidement et plus facilement.</p>}
        {!accountCreatePending && !connectedAccountId && (
          <div>
            <button
              className="button is-primary"
              onClick={async () => {
                setAccountCreatePending(true);
                setError(false);
                fetch("/createStripeAccount", {
                  method: "GET",
                })
                  .then((response) => response.json())
                  .then((json) => {
                    setAccountCreatePending(false);
                    const { account, error } = json;

                    if (account) {
                      console.log(account);
                      setConnectedAccountId(account);
                    }

                    if (error) {
                      setError(true);
                    }
                  });
              }}
            >
              Créer un compte Stripe
            </button>
          </div>
        )}
        {stripeConnectInstance && (
          <ConnectComponentsProvider connectInstance={stripeConnectInstance}>
            <ConnectAccountOnboarding
              onExit={() => {
                setOnboardingExited(true);
                fetch("/setUserStripeRegistered", {
                  method: "GET",
                });
              }}
            />
          </ConnectComponentsProvider>
        )}
        {error && <p className="error">Une erreur est survenue</p>}
        {(connectedAccountId || accountCreatePending || onboardingExited) && (
          <div className="dev-callout">
            {connectedAccountId && <p>Votre compte connecté est: <code className="bold">{connectedAccountId}</code></p>}
            {accountCreatePending && <p>Création du compte...</p>}
            {onboardingExited && <p>Le composant de création de compte est sorti</p>}
          </div>
        )}
      </div>
    </div>
  );
}