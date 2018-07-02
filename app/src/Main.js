import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import {HashRouter, Route, Link} from 'react-router-dom'

import HomeComponent from "./Components/Home/Home";
import ProjectComponent from "./Components/Project/Project";
import NotFoundComponent from "./Components/Shared/NotFound/NotFound"
import {Switch} from "react-router";
import HeaderComponent from "./Components/Shared/Header/Header";
import FooterComponent from "./Components/Shared/Footer/Footer";

const e = React.createElement;

class App extends Component {
    render() {
        return (
            <HashRouter
                basename="/">
                <div className="App">
                    <HeaderComponent/>
                    <Switch>
                        <Route exact path="/" component={HomeComponent}/>
                        <Route path="/project/:projectID/:password" component={ProjectComponent}/>
                        <Route component={NotFoundComponent}/>
                    </Switch>
                    <FooterComponent/>
                </div>
            </HashRouter>

        );
    }
}

const domContainer = document.querySelector('#app-root');
ReactDOM.render(e(App), domContainer);