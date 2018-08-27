import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import {HashRouter} from 'react-router-dom'

import HomeComponent from "./Components/Home/Home.component";
import ProjectComponent from "./Components/Project/Project.component";
import NotFoundComponent from "./Components/Shared/NotFound/NotFound.component"
import {Switch} from "react-router";
import Layout from "./Components/Shared/Layout/Layout.component";

const e = React.createElement;

class App extends Component {
    render() {
        return (
            <HashRouter
                basename="/">
                <div className="App">
                    <Switch>
                        <Layout exact path="/" component={() => <HomeComponent/>}/>
                        <Layout path="/project/:jobID/:password" component={() => ProjectComponent}/>
                        <Layout component={() => NotFoundComponent}/>
                    </Switch>
                </div>
            </HashRouter>
        );
    }
}

const domContainer = document.querySelector('#app-root');
ReactDOM.render(e(App), domContainer);