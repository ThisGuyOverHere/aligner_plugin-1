import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";
import ProjectStore from "../../../../Stores/Project.store";
import ProjectConstants from "../../../../Constants/Project.constants";
import SystemConstants from "../../../../Constants/System.constants";
import SystemStore from "../../../../Stores/System.store";

class ToolbarContextualNavigationComponent extends Component {

    constructor(props) {
        super(props);
        this.state = {
            inDrag: false,
            merge: false
        };
    }


    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.MERGE_STATUS, this.mergeStatus);
        SystemStore.addListener(SystemConstants.IN_DRAG, this.dragStatus);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.MERGE_STATUS, this.mergeStatus);
        SystemStore.removeListener(SystemConstants.IN_DRAG, this.dragStatus);
    }

    render() {
        let mergeClasses=[]
        if(this.state.merge){
            mergeClasses.push('active')
        }


        return (
            <div>
                {this.state.inDrag ? <div className="cmd-shortcut">
                        <p><span className={mergeClasses.join(" ")}>ALT+MOVE</span> to merge</p>
                    </div>
                    : <div className="navigation">
                        <ul className="inline-list align-middle" style={{float: 'right'}}>
                            <li className="list-inline__item">
                                <a href="https://www.matecat.com/open-source/" target="_blank">Open source</a>
                            </li>
                            <li className="list-inline__item">
                                <a target="_blank" href="/api/docs">API</a>
                            </li>
                            <li className="list-inline__item">
                                <a href="https://www.matecat.com/terms" target="_blank">Terms</a>
                            </li>
                            <li className="list-inline__item">
                                <a href="https://blog.matecat.com" target="_blank">Blog</a>
                            </li>
                        </ul>
                    </div>}

            </div>
        );
    }

    mergeStatus = (status) => {
        this.setState({
            merge: status
        });
    };

    dragStatus = (status) => {
        this.setState({
            inDrag: status
        });
    };


}

export default ToolbarContextualNavigationComponent;