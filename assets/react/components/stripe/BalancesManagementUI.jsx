// Include this React component
import {
    ConnectBalances,
    ConnectComponentsProvider,
  } from "@stripe/react-connect-js";

  import React from 'react';
  
  export default function BalancesManagementUI({stripeConnectInstance}) {
    return (
        <ConnectComponentsProvider connectInstance={stripeConnectInstance}>
            <ConnectBalances />
        </ConnectComponentsProvider>
    );
  }