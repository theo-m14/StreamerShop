// Include this React component
import React from 'react';
import {
    ConnectNotificationBanner,
    ConnectComponentsProvider,
  } from '@stripe/react-connect-js';
  
  export default function NotificationBannerUI({ stripeConnectInstance }) {
    return (
      <ConnectComponentsProvider connectInstance={stripeConnectInstance}>
        <ConnectNotificationBanner
          // Optional:
           collectionOptions={{
            fields: 'eventually_due',
            futureRequirements: 'include',
          }}
        />
      </ConnectComponentsProvider>
    );
  };