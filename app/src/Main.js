import React, {Component} from 'react';
import ReactDOM from 'reactDOM';

class App extends Component {
    render() {
        return (
            <div className="App">
                <h2>Welcome to React</h2>
            </div>
        );
    }
}

const domContainer = document.querySelector('#app-root');
ReactDOM.render(e(App), domContainer);