import React, { useState, useRef } from "react";
import AdressSelection from "./AdressSelection";
export default function ShipmentForm({ shipment, orders, setShipment, user, setOrders }) {

    const handleSelectAdress = (adress) => {
        setShipment({...shipment, adress: adress});
    }

    const [packageType,setPackageType] = useState("PARCEL");

    const [displayParcelPoint, setDisplayParcelPoint] = useState(false);

    const [shipmentCreated, setShipmentCreated] = useState(false);

    const widthRef = useRef(null);
    const lengthRef = useRef(null);
    const heightRef = useRef(null);
    const weightRef = useRef(null);

    const descriptionRef = useRef(null);    
    const categoryRef = useRef(null);

    const [contentError, setContentError] = useState(false);
    const [dimensionError, setDimensionError] = useState(false);

    const [productCategories, setProductCategories] = useState([]);

    //fonction qui récupère les id de catégories de produits boxtal
    const getProductCategories = () => {
        fetch("/vendor/getProductCategories")
        .then(response => response.json())
        .then(data => {
            data = data.content
            setProductCategories(data);
        })
        .catch(error => {
            console.error("Erreur lors de la récupération des catégories de produits", error);
        });
    }

    const resetShipment = () => {
        //refresh la page
        window.location.reload();
    }


    //Fonction pour créer l'expédition en base de données
    const handleCreateShipment = () => {

        fetch("/vendor/createShipment", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(shipment),
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);
            setShipmentCreated(true);
            //On update le statut des commandes selectionné en "batched"
            orders.filter(order => order.isSelected).forEach(order => {
                order.statut.statut = "batched";
            });
            setOrders([...orders]);
        })
        .catch(error => {
            console.error("Erreur lors de la création de l'expédition", error);
        });
    }

    //Fonction pour créer les dimensions du colis
    const createPackageDimensions = () => {
        // Validation des champs null et supérieur à 0
        if (!widthRef.current.value || widthRef.current.value == 0 || !lengthRef.current.value || lengthRef.current.value == 0 || !weightRef.current.value || weightRef.current.value == 0 || 
            (packageType !== "LETTER" && (!heightRef.current.value || heightRef.current.value == 0))) {
            setDimensionError(true);
            return;
        }
        
        //on set le type d'envois
        setShipment({...shipment, packages: [{...shipment.packages[0], type: packageType}]});
        //on set les dimensions du colis
        setShipment({...shipment, packages: [{...shipment.packages[0], dimension: {
            width: widthRef.current.value, 
            length: lengthRef.current.value, 
            height: heightRef.current ? heightRef.current.value : null, 
            weight: weightRef.current.value
        }}]});
        getProductCategories();
    }

    //Fonction pour créer le contenu du colis
    const handleCreateContent = () => {
        // Validation de la description et de la catégorie
        const description = descriptionRef.current.value;
        const category = categoryRef.current.value;

        if (!description || !category) {
            setContentError(true);
            return;
        }

        setShipment({...shipment, packages: [{...shipment.packages[0], content: { id: category,description: description}}]})
    }

    //Fonction pour réinitialiser le contenu du colis
    const resetContent = () => {
        setShipment({...shipment, packages: [{...shipment.packages[0], content: null}]});
    }

    //Fonction pour enregistrer l'adresse du vendeur depuis le formulaire de point de livraison
    const handleAdressRegistration = (adress) => {
        if(adress.registerAdress) {
            registerUserAdress(adress);
        }
        //on récupère le code du pays avec les deux premiers caractères en majuscule
        adress.countryCode = adress.country.slice(0, 2).toUpperCase();
        setShipment({...shipment, vendorAdress: adress});
    }

    //Fonction pour enregistrer l'adresse du vendeur depuis le formulaire de point de livraison
    const registerUserAdress = (adress) => {
        fetch("/vendor/registerAdress", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(adress),
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);
        })
        .catch(error => {
            console.error("Erreur lors de l'enregistrement de l'adresse", error);
        });
    }

    //Fonction pour sélectionner le point de livraison depuis les choix prédéfinis
    const handleSelectParcelPoint = (adress) => {
        console.log(adress);
        adress = {
            city: adress.city,
            country: adress.country,
            email: adress.contact.email,
            firstName: adress.contact.firstName,
            lastName: adress.contact.lastName,
            countryCode: adress.countryCode,
            parcelPoint : {
                name: adress.parcelPointName,
                code: adress.parcelPointCode,
            },
            phone: adress.contact.phone,
            street: adress.adressLine,
            zipCode: adress.postalCode
        }
        setShipment({...shipment, vendorAdress: adress});
    }
    
    return (
        <div className="box pb-6">
            <h2 className="has-text-centered is-size-4">Créer une expédition</h2>
                        {/* Si l'adresse n'est pas sélectionnée, afficher les adresses de livraison */}
                        {!shipment.adress && <div className="adressSelection">
                            <h3 className="has-text-centered is-size-6 mb-4">Adresse de livraison</h3>
                            {/* Afficher les adresses de livraison depuis les commandes sélectionnées */}
                            <div className="is-flex is-flex-wrap-wrap mt-5 is-justify-content-space-evenly">
                            {orders.filter((order) => order.isSelected).map((order) => (

                                <div key={order.id} className="box mb-3">
                                    <p>{order.adress.contact.firstName} {order.adress.contact.lastName}</p>
                                    <p>{order.adress.postalCode}</p>
                                    <p>{order.adress.city}</p>


                                    <p>{order.adress.adressLine}</p>
                                    <div className="buttons is-centered">
                                        <button className="button is-primary mt-4" onClick={() => handleSelectAdress(order.adress)}>Sélectionner</button>
                                    </div>
                                </div>
                            ))}
                            </div>
                        </div>}
                        {/* Si l'adresse est sélectionnée, afficher les produits */}
                        {shipment.adress && !shipment.productConfirmation && <div className="productResume">
                            <h3 className="has-text-centered is-size-6 mb-4">Produits</h3>
                            {orders.filter((order) => order.isSelected).map((order) => (
                                order.orderItem.map((item) => (
                                    console.log(item),
                                    <div key={item.id} className="box is-flex is-flex-direction-row is-align-items-center is-justify-content-space-between"> 
                                        <figure className="image is-128x128">
                                            <img src={`/images/products/${item.product.imageName}`} alt={item.product.title} />
                                        </figure>
                                        <p>{item.product.title} x{item.quantity}</p>
                                    </div>
                                ))
                            ))}
                            <div className="buttons is-centered">
                                {/* bouton de retour qui remet la l'adresse à null */}
                                <button className="button is-danger mt-4" onClick={() => setShipment({...shipment, adress: null})}>Retour</button>
                                <button className="button is-primary mt-4" onClick={() => {setShipment({...shipment, productConfirmation: true}); setDimensionError(false);}}>Valider</button>
                            </div>
                        </div>}
                        {/* Si les produits sont validés, afficher le formulaire de taille et type de colis */}
                        {shipment.productConfirmation && shipment.packages[0].dimension.width === null && <div className="packageForm">
                            <h3 className="has-text-centered is-size-6 mb-4">Taille et type de colis</h3>
                                <div className="tabs is-centered is-toggle is-toggle-rounded">
                                    <ul>
                                        <li className={packageType === "PARCEL" ? "is-active" : ""}>
                                            <a onClick={() => setPackageType("PARCEL")}>
                                                <span className="icon is-small"><i className="fas fa-box"></i></span>
                                                <span>Colis</span>
                                            </a>
                                        </li>
                                        <li className={packageType === "LETTER" ? "is-active" : ""}>
                                            <a onClick={() => setPackageType("LETTER")}>
                                                <span className="icon is-small"><i className="fas fa-envelope"></i></span>
                                                <span>Lettres</span>
                                            </a>
                                        </li>
                                        {/* <li className={packageType === "PALLET" ? "is-active" : ""}>
                                            <a onClick={() => setPackageType("PALLET")}>
                                                <span className="icon is-small"><i className="fas fa-truck"></i></span>
                                                <span>Palettes</span>
                                            </a>
                                        </li> */}
                                    </ul>
                                </div>
                                {dimensionError && <div className="notification is-danger">
                                    <button className="delete" onClick={() => setDimensionError(false)}></button>
                                    <p>Veuillez remplir tous les champs pour les dimensions du colis</p>
                                </div>}
                                <form className="box">
                                    <div className="field">
                                        <label className="label">Largeur (cm)</label>
                                        <div className="control">
                                            <input className="input" type="number" placeholder="Largeur" min="1" ref={widthRef} />
                                        </div>
                                    </div>
                                    <div className="field">
                                        <label className="label">Longueur (cm)</label>
                                        <div className="control">
                                            <input className="input" type="number" placeholder="Longueur" min="1" ref={lengthRef} />
                                        </div>
                                    </div>
                                    {packageType !== "LETTER" && <div className="field">
                                        <label className="label">Hauteur (cm)</label>
                                        <div className="control">
                                            <input className="input" type="number" placeholder="Hauteur" min="1" ref={heightRef} />
                                        </div>
                                    </div>}
                                    <div className="field">
                                        <label className="label">Poids (kg)</label>
                                        <div className="control">
                                            {/* Si on press enter, on appelle la fonction createPackageDimensions */}
                                            <input className="input" type="number" placeholder="Poids" min="1" ref={weightRef} onKeyDown={(e) => {if(e.key === "Enter") {createPackageDimensions();setContentError(false);}}} />
                                        </div>
                                    </div>


                                </form>
                                <div className="buttons is-centered">
                                    {/* bouton de retour qui remet la propriété productConfirmation à false */}
                                    <button className="button is-danger mt-4" onClick={() => setShipment({...shipment, productConfirmation: false})}>Retour</button>
                                    <button className="button is-primary mt-4" onClick={() => {createPackageDimensions();setContentError(false)}}>Suivant</button>
                                </div>
                        </div>}
                        {/* Si les dimensions du colis sont créées, afficher le formulaire de contenu */}
                        {shipment.packages[0].dimension.width && shipment.packages[0].content === null && <div className="packageContent">
                            <h3 className="has-text-centered is-size-6 mb-4">Contenu</h3>
                            {contentError && <div className="notification is-danger">
                                <button className="delete" onClick={() => setContentError(false)}></button>
                                <p>Veuillez remplir tous les champs pour le contenu du colis</p>
                            </div>}
                            <form className="box">
                                <div className="field">
                                    <label className="label">Description du contenu</label>
                                    <div className="control">
                                        <input className="input" type="text" placeholder="Description du contenu" ref={descriptionRef} />
                                    </div>
                                </div>
                                <div className="field">
                                    <label className="label">Catégorie</label>
                                    <div className="control">
                                        <select className="input" type="text" placeholder="Catégorie" ref={categoryRef}>
                                            {productCategories.map((category) => (
                                                <option key={category.id} value={category.id}>{category.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </form>
                            <div className="buttons is-centered">
                                {/* bouton de retour qui remet les dimensions à null */}
                                <button className="button is-danger mt-4" onClick={() => setShipment({...shipment, packages: [{...shipment.packages[0], dimension: {width: null, length: null, height: null, weight: null}}]})}>Retour</button>
                                <button className="button is-primary mt-4" onClick={handleCreateContent}>Suivant</button>
                            </div>
                        </div>}
                        {/* Si le contenu est créé, afficher le formulaire de point de livraison */}
                        {shipment.packages[0].content && !shipment.vendorAdress && <div className="packageParcelPoint">
                            <h3 className="has-text-centered is-size-6 mb-4">Point de dépot</h3>
                            {/* On affiche les adresses enregistrés de l'utilsateur */}
                            {!displayParcelPoint && <div className="container is-flex is-flex-wrap-wrap is-justify-content-space-evenly">
                                {user.adress.map((adress) => (
                                    <div className="box p-5 mb-3" key={adress.id} style={{width: 'fit-content', margin: '0 auto'}}>
                                        <p>{adress.contact.firstName} {adress.contact.lastName}</p>
                                        <p>{adress.parcelPointName}</p>
                                        <p>{adress.postalCode}</p>
                                        <p>{adress.city}</p>
                                        <p>{adress.adressLine}</p>
                                        <div className="buttons is-centered">
                                            <button className="button is-primary mt-4" onClick={() => handleSelectParcelPoint(adress)}>Sélectionner</button>
                                        </div>
                                    </div>
                                ))}
                                <div className="box p-6 mb-3 is-flex is-justify-content-center is-align-items-center" style={{width: 'fit-content', margin: '0 auto'}}>
                                    <button className="button is-primary is-rounded" onClick={() => setDisplayParcelPoint(!displayParcelPoint)}>
                                        <i className="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>}
                            {displayParcelPoint && <AdressSelection handleAdressRegistration={handleAdressRegistration} userIsConnected={true} />}
                            <div className="buttons is-centered">
                                <button className="button is-danger mt-4" onClick={() => resetContent()}>Retour</button>
                            </div>
                        </div>}
                        {shipment.vendorAdress && !shipmentCreated && <div className="mt-5">
                            <div className="buttons is-centered  ">
                                <button className="button is-primary" onClick={handleCreateShipment}>Créer l'expédition</button>

                            </div>
                            <div className="buttons is-centered">
                                {/* bouton de retour qui remet la propriété vendorAdress à null */}
                                <button className="button is-danger mt-4" onClick={() => setShipment({...shipment, vendorAdress: null})}>Retour</button>
                            </div>
                        </div>}
                        {shipmentCreated && <div className="mt-5">
                            <h3 className="has-text-centered is-size-6 mb-4">Expédition créée</h3>
                            <div className="buttons is-centered  ">
                                <button className="button is-primary" onClick={() => resetShipment()}>Créer une nouvelle expédition</button>
                                <a href={`/vendor/shipment`} className="button is-primary">Voir mes expéditions</a>
                            </div>
                        </div>}
        </div>
    )
}