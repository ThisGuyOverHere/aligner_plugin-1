import React, {Component} from 'react';
import {Route} from 'react-router-dom'
import HeaderComponent from "../Header/Header.component";
import FooterComponent from "../Footer/Footer.component";

const Layout = ({component: Component, ...rest}) => {
    return (
        <Route {...rest} render={matchProps => (
            <div className="DefaultLayout">
                <HeaderComponent/>
                <Component {...matchProps} />
                <FooterComponent/>
            </div>
        )} />
    )
};

export default Layout;