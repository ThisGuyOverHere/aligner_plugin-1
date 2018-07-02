import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import {HashRouter, Route, Link} from 'react-router-dom'

import HomeComponent from "./Components/Home/Home";
import ProjectComponent from "./Components/Project/Project";
import NotFoundComponent from "./Components/Shared/NotFound/NotFound"
import {Switch} from "react-router";

const e = React.createElement;

class App extends Component {
    render() {
        return (
            <HashRouter
                basename="/">
                <div className="App">
                    <div>
                        <Link to="/">Home</Link>
                        <Link to="/project/123/123">Project</Link>
                        <Link to="/not-found">Not Found</Link>
                    </div>
                    <Switch>
                        <Route exact path="/" component={HomeComponent}/>
                        <Route path="/project/:projectID/:password" component={ProjectComponent}/>
                        <Route component={NotFoundComponent}/>
                    </Switch>
                </div>
            </HashRouter>
        );
    }
}

const domContainer = document.querySelector('#app-root');
ReactDOM.render(e(App), domContainer);