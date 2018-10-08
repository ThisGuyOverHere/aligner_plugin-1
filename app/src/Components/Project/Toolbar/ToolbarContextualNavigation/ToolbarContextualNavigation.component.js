import React, {Component} from 'react';
import ProjectStore from "../../../../Stores/Project.store";
import ProjectConstants from "../../../../Constants/Project.constants";
import SystemConstants from "../../../../Constants/System.constants";
import SystemStore from "../../../../Stores/System.store";

class ToolbarContextualNavigationComponent extends Component {

    constructor(props) {
        super(props);
        this.state = {
            inDrag: false,
            merge: false,
            type_of_action: null,
        };
    }


    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.MERGE_STATUS, this.mergeStatus);
        ProjectStore.addListener(ProjectConstants.ON_ACTION_HOVER, this.setActionHoveredType);
        SystemStore.addListener(SystemConstants.IN_DRAG, this.dragStatus);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.MERGE_STATUS, this.mergeStatus);
        ProjectStore.removeListener(ProjectConstants.ON_ACTION_HOVER, this.setActionHoveredType);
        SystemStore.removeListener(SystemConstants.IN_DRAG, this.dragStatus);
    }

    render() {
        let mergeClasses = [];
        if (this.state.merge) {
            mergeClasses.push('active')
        }

        switch (this.state.type_of_action) {
            case 'split':
                return (
                    <div>
                        <div className="cmd-shortcut">
                            <p><span className={mergeClasses.join(" ")}>alt+S</span> to split</p>
                            <i className="icon question circle outline"></i>
                        </div>
                    </div>
                );
            case 'reverse':
                return (
                    <div>
                        <div className="cmd-shortcut">
                            <p><span className={mergeClasses.join(" ")}>alt+R</span> to reverse</p>
                            <i className="icon question circle outline"></i>
                        </div>
                    </div>
                );
            case 'merge':
                return (
                    <div>
                        <div className="cmd-shortcut">
                            <p><span className={mergeClasses.join(" ")}>alt+M</span> to merge</p>
                            <i className="icon question circle outline"></i>
                        </div>
                    </div>
                );
            case 'align':
                return (
                    <div>
                        <div className="cmd-shortcut">
                            <p><span className={mergeClasses.join(" ")}>alt+A</span> to align</p>
                            <i className="icon question circle outline"></i>
                        </div>
                    </div>
                );
            case 'merge&align':
                return (
                    <div>
                        <div className="cmd-shortcut">
                            <p><span className={mergeClasses.join(" ")}>alt+W</span> to Merge & Align</p>
                            <i className="icon question circle outline"></i>
                        </div>
                    </div>
                );
            default:
                return (
                    <div>
                        {this.state.inDrag ? <div className="cmd-shortcut">
                                <p><span className={mergeClasses.join(" ")}>alt + drop</span> to merge</p>
                            </div>
                            : <div className="cmd-shortcut">
                                <p><span>select </span> or <span>drag and drop </span> to align</p>
                                <i className="icon question circle outline"></i>
                            </div>}
                    </div>
                );
        }
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

    setActionHoveredType = (type) => {
        this.setState({
            type_of_action: type
        });
    };


}

export default ToolbarContextualNavigationComponent;