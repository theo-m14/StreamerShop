import React, { useState, useEffect } from 'react';

export default function ThemeToggle() {
    //récupérer l'userpreference dans le systeme
    const userPreference = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const [theme, setTheme] = useState(localStorage.getItem('theme') || userPreference);

    const changeTheme = (theme) => {
        setTheme(theme);
        // change  html element class
        document.querySelector('html').classList.toggle('theme-light', theme === 'light');
        document.querySelector('html').classList.toggle('theme-dark', theme === 'dark');
        localStorage.setItem('theme', theme);
    }

    useEffect(() => {
        changeTheme(theme);
    }, [theme]);

    return (
        <button className="button is-primary is-rounded is-medium is-centered" id="theme-toggle" onClick={() => changeTheme(theme === 'light' ? 'dark' : 'light')}>
            <span className="icon">
                {theme === 'light' ? <i className="fas fa-sun"></i> : <i className="fas fa-moon"></i>}
            </span>
        </button>
    )
}