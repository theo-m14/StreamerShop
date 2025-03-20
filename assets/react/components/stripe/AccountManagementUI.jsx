// Include this React component
import {
    ConnectAccountManagement,
    ConnectComponentsProvider,
  } from '@stripe/react-connect-js';

  import React from 'react';
  
  export default function AccountManagementUI({stripeConnectInstance}) {
    return (
      <ConnectComponentsProvider connectInstance={stripeConnectInstance}>
        <ConnectAccountManagement
          // Optional:
          // collectionOptions={{
          //   fields: 'eventually_due',
          //   futureRequirements: 'include',
          // }}
        />
      </ConnectComponentsProvider>
    );
  };