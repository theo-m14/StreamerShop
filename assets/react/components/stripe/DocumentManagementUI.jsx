import {
    ConnectDocuments,
    ConnectComponentsProvider,
  } from "@stripe/react-connect-js";

  import React from 'react';

export default function DocumentManagementUI({stripeConnectInstance}) {
    return (        
      <div className="container">
      <ConnectComponentsProvider connectInstance={stripeConnectInstance}>
        <ConnectDocuments />
        </ConnectComponentsProvider>
        </div>
    )
}