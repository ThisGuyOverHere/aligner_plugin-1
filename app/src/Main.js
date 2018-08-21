import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import {HashRouter, Route, Link} from 'react-router-dom'

import HomeComponent from "./Components/Home/Home.component";
import ProjectComponent from "./Components/Project/Project.component";
import NotFoundComponent from "./Components/Shared/NotFound/NotFound.component"
import {Switch} from "react-router";
import HeaderComponent from "./Components/Shared/Header/Header.component";
import FooterComponent from "./Components/Shared/Footer/Footer.component";

const e = React.createElement;

class App extends Component {
    render() {
        return (
            <HashRouter
                basename="/">
                <div className="App">
                    <HeaderComponent/>
                    <Switch>
                        <Route exact path="/" component={() => <HomeComponent/>} />
                        <Route path="/project/:jobID/:password" component={ProjectComponent}/>
                        <Route component={NotFoundComponent}/>
                    </Switch>
                    {/*<FooterComponent/>*/}
                </div>
            </HashRouter>
        );
    }
}
const domContainer = document.querySelector('#app-root');
ReactDOM.render(e(App), domContainer);