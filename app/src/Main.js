import "babel-polyfill"
import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import {HashRouter} from 'react-router-dom'

import HomeComponent from "./Components/Home/Home.component";
import JobComponent from "./Components/Align/Job/Job.component";
import NotFoundComponent from "./Components/Shared/NotFound/NotFound.component";
import AnalyseComponent from "./Components/Analyse/Analyse.component";

import {Switch} from "react-router";
import Layout from "./Components/Shared/Layout/Layout.component";
import AlignComponent from "./Components/Align/Align.component";


const e = React.createElement;

const App = () => (
    <HashRouter
        basename="/">
        <div className="App">
            <Switch>
                <Layout exact path="/" component={HomeComponent}/>
                <Layout path="/job/:jobID/:password/align" component={AlignComponent}/>
                <Layout path="/job/:jobID/:password/pre-align" hideToolbar={true} component={AnalyseComponent}/>
                <Layout component={NotFoundComponent}/>
            </Switch>
        </div>
    </HashRouter>
);


const domContainer = document.querySelector('#app-root');
ReactDOM.render(e(App), domContainer);
