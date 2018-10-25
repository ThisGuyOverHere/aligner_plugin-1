import React, {Component} from 'react';
import PropTypes from "prop-types";
import SystemActions from "../../../../Actions/System.actions";
import {Redirect} from "react-router";

class ExportModalCompleted extends Component {

    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
    };

    constructor(props) {
        super(props);
        this.state = {
        };
    }

    componentDidMount = () => {

    };

    componentWillUnmount = () => {

    };

    render() {
        return (
            <div id="completed">
                <h1>Export completed</h1>
                <h3>your tmx has been exported correctly.</h3>

                <div className="btn-container">
                    <button className="close-btn ui button" tabIndex="6" type="" onClick={this.onCloseExportModal}>
                        Close
                    </button>
                    <button className="newalign-btn ui button" tabIndex="6" type="" >
                       New Align
                    </button>
                </div>
            </div>
        );
    }

    onCloseExportModal = () => {
        SystemActions.setExportModalStatus(false);
    };

}

export default ExportModalCompleted;
