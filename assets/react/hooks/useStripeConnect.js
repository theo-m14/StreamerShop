import { useState, useEffect } from "react";
import { loadConnectAndInitialize } from "@stripe/connect-js";

export const useStripeConnect = (connectedAccountId) => {
  const [stripeConnectInstance, setStripeConnectInstance] = useState();

  let currentTheme = document.documentElement.classList.contains("theme-light") ? "light" : "dark";

  useEffect(() => {
    if (connectedAccountId) {
      const fetchClientSecret = async () => {
        const response = await fetch("/createStripeSession", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            account: connectedAccountId,
          }),
        });

        if (!response.ok) {
          // Handle errors on the client side here
          const { error } = await response.json();
          throw ("An error occurred: ", error);
        } else {
          const { client_secret: clientSecret } = await response.json();
          return clientSecret;
        }
      };

      setStripeConnectInstance(
        loadConnectAndInitialize({
          publishableKey: "pk_test_51O0mx9Ddah1uLYmzf0SMrTBkSFADmF9sR63AdXlea9ZT8OOfMrzhQIBb62SVTIiTwrnbYbV8QXPJsvtooYRudZb600UHHJ3MFq",
          fetchClientSecret,
          appearance: {
            overlays: "dialog",
            variables: {
              colorPrimary: "rgb(0, 184, 156)",
              colorBackground: currentTheme === "light" ? "rgb(255, 255, 255)" : "rgb(20, 22, 26)",
              buttonPrimaryBackground: "rgb(0, 184, 156)",
            },
          },
        })
      );
    }
  }, [connectedAccountId]);

  return stripeConnectInstance;
};

export default useStripeConnect;