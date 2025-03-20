// Include this React component
import {
    ConnectPayments,
    ConnectComponentsProvider,
  } from "@stripe/react-connect-js";
  
  import React from 'react';

  export default function PaymentManagementUI({stripeConnectInstance}) {
    return (
        <ConnectComponentsProvider connectInstance={stripeConnectInstance}>
            <ConnectPayments
        // Optional: specify filters to apply on load
        // defaultFilters={{
        //   amount: {greaterThan: 100},
        //   date: {before: new Date(2024, 0, 1)},
        //   status: ['partially_refunded', 'refund_pending', 'refunded'],
        //   paymentMethod: 'card',
        // }}
      />
        </ConnectComponentsProvider>
    );
}