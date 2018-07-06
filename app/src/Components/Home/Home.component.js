import React, {Component} from 'react';
import PropTypes from "prop-types";

class HomeComponent extends Component {
    render() {
        return (
            <div>
                <p>{this.props.label}</p>
            </div>
        );
    }
}
HomeComponent.propTypes = {
    label: PropTypes.string.isRequired
};
export default HomeComponent;