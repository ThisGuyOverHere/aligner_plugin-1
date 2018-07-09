import React, {Component} from 'react';
import PropTypes from "prop-types";

class HomeComponent extends Component {
    constructor() {
        super();
    }

    bar = () => {
        console.log(this.props.label);
    };

    render() {
        return (
            <div>
                <p>{this.props.label}</p>
                <button onClick={this.bar}>prova</button>
            </div>
        );
    }
}
HomeComponent.propTypes = {
    label: PropTypes.string.isRequired
};
export default HomeComponent;