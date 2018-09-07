import React, {Component} from 'react';
import SystemActions from "../../../Actions/System.actions";
import ProjectActions from "../../../Actions/Project.actions";
import ProjectStore from "../../../Stores/Project.store";
import ProjectConstants from "../../../Constants/Project.constants";
import PropTypes from "prop-types";

class SplitComponent extends Component {
    static propTypes = {
        segment: PropTypes.object,
    };

    constructor(props) {
        super(props);
        this.state = {
            splitModalStatus: false,
        };
    }

    render = () => {
        return (
            <div>
                <div>
                    <div className="overlay" onClick={this.onCloseSplitModal}>
                    </div>
                    <div className="resetContainer">
                        <div className="header">
                            <div className="sx-header">
                                <img src="/public/img/logo-ico.png"></img>
                                <h1>Split Segment</h1>
                            </div>
                            <div className="dx-header">
                            <span onClick={this.onCloseSplitModal}>
                                <i className="icon window close"></i>
                            </span>
                            </div>
                        </div>
                        <div className="content">


                        </div>
                    </div>
                </div>
            </div>
        );
    };

    onCloseSplitModal = () => {
        ProjectActions.splitModalStatus(false);
        ProjectActions.setSegmentToSplit({});
    };


    componentDidMount() {

    }

    componentWillUnmount() {

    }
}

export default SplitComponent;