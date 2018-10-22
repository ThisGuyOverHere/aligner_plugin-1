import "babel-polyfill"
import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import {HashRouter} from 'react-router-dom'

import HomeComponent from "./Components/Home/Home.component";
import JobComponent from "./Components/Project/Job.component";
import NotFoundComponent from "./Components/Shared/NotFound/NotFound.component";
import AnalyseComponent from "./Components/Analyse/Analyse.component";

import {Switch} from "react-router";
import Layout from "./Components/Shared/Layout/Layout.component";


const e = React.createElement;

const App = () => (
    <HashRouter
        basename="/">
        <div className="App">
            <Switch>
                <Layout exact path="/" component={HomeComponent}/>
                <Layout path="/job/:jobID/:password/align" component={JobComponent}/>
                <Layout path="/job/:jobID/:password/pre-align" component={AnalyseComponent}/>
                <Layout component={NotFoundComponent}/>
            </Switch>
        </div>
    </HashRouter>
);


const domContainer = document.querySelector('#app-root');
ReactDOM.render(e(App), domContainer);
