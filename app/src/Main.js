import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter, Route, Link } from 'react-router-dom'

import HomeComponent from "./Components/Home/Home";
const e = React.createElement;

class App extends Component {
    render() {
        return (
            <BrowserRouter
                basename="plugins/aligner/index">
                <div className="App">
                    <div><Link to="/dashboard">Dashboard</Link></div>
                    <Route path="/dashboard" component={HomeComponent}/>
                </div>
            </BrowserRouter>
        );
    }
}

const domContainer = document.querySelector('#app-root');
ReactDOM.render(e(App), domContainer);