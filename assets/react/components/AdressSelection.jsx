import React, { useState, useRef } from "react";
import { BoxtalParcelPointMap } from '@boxtal/parcel-point-map';

export default function () {
    const [adress, setAdress] = useState(null);
    let boxtalParcelPointMap = null;

    const country = useRef(null);
    const zipCode = useRef(null);
    const city = useRef(null);
    const street = useRef(null);

    fetch('https://api.boxtal.build/iam/account-app/token', {
        method: 'POST',
        headers: {
            'Authorization': 'Basic ' + btoa('UQL5NLC79MI03U32AJQJDV79ZZU5LOANYD57G21K:81b18827-16b5-4b2c-9cfc-6842cfd9be1a')
        }
    })
        .then(response => response.json())
        .then(data => {
            console.log(data.accessToken);
            const token = data.accessToken;
            boxtalParcelPointMap = new BoxtalParcelPointMap({
                domToLoadMap: '#parcel-point-map', // le sélecteur correspond à l'élément qui doit accueillir la carte
                accessToken: token, // le token récupéré via le endpoint d'authentification
                baseUrl: 'https://maps.boxtal.build/app/v3/index.html',
                config: {
                    locale: 'fr', // langue de l'interface, optionnel, valeurs possibles fr ou en
                    parcelPointNetworks: [ // la liste de réseaux à afficher
                        {
                            code: 'CHRP_NETWORK', // code du réseau, ici le code pour Chronopost
                            markerTemplate: {
                                color: '#94a1e8' // il est possible de surcharger la couleur du marker pour chaque réseau
                            },
                        }
                    ],
                    options: {
                        primaryColor: '#00FA9A', // couleur des boutons (et des markers si pas surchargée)
                        autoSelectNearestParcelPoint: true // le plus relais le plus proche sera sélectionné par défaut dès la recherche effectuée
                    },

                },
                onMapLoaded: () => {
                    searchShipperParcelPoints({
                        country: 'FR',
                        zipCode: '24000',
                        city: 'Périgueux',
                        street: '10 Rue Eguillerie',
                    });
                }
            });
        });

    function searchShipperParcelPoints(params) {
        boxtalParcelPointMap.searchParcelPoints(
            params,
            (parcelPoint) => console.log('selected parcelPoint', parcelPoint)
        );
    }

    function updateAdress(event) {
        event.preventDefault();
        let adresse =
        {
            country: "FR",
            zipCode: zipCode.current.value,
            city: city.current.value,
            street: street.current.value,
        }
        console.log(adresse);
        searchShipperParcelPoints(adresse);
    }

    return (
        <div>
            {/* Formulaire de saisie de l'adresse */}
            <form>
                <label htmlFor="pays">Pays</label>
                <input type="text" name="pays" placeholder="Pays" ref={country} defaultValue="France" />
                <label htmlFor="codePostal">Code postal</label>
                <input type="text" name="codePostal" placeholder="Code postal" ref={zipCode} />
                <label htmlFor="ville">Ville</label>
                <input type="text" name="ville" placeholder="Ville" ref={city} />
                <label htmlFor="rue">Rue</label>
                <input type="text" name="rue" placeholder="Rue" ref={street} />
                <button type="submit" onClick={updateAdress}>Rechercher</button>
            </form>
            <div id="parcel-point-map" style={{ height: '500px' }}></div>
        </div>
    );
}