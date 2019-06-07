import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import Hotkeys from "react-hot-keys";

class ToolbarActionsMergeComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired,
        jobConf: PropTypes.shape({
            password: PropTypes.string,
            id: PropTypes.any
        })
    };

    constructor(props) {
        super(props);
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        let disabled = false;
        if (
            !((this.props.selection.source.count === 0 && this.props.selection.target.count > 1)
                || (this.props.selection.target.count === 0 && this.props.selection.source.count > 1))
        ) {
            disabled = true;
        }
        return <Hotkeys
            keyName="alt+m"
            onKeyDown={this.onMergeClick}>
            <button
                disabled={disabled}
                onClick={this.onMergeClick}>
                Merge
            </button>
        </Hotkeys>;
    }

    onMergeClick = () => {
        if (
            ((this.props.selection.source.count === 0 && this.props.selection.target.count > 1)
                || (this.props.selection.target.count === 0 && this.props.selection.source.count > 1))
        ) {
            const type = this.props.selection.source.count > 0 ? 'source' : 'target';
            const orders = this.props.selection[type].list.sort((a,b)=>{return a-b});
            ProjectActions.mergeSegments(this.props.jobConf.id, this.props.jobConf.password, orders, type);
            ProjectActions.addSegmentToSelection(-1);
            ProjectActions.onActionHover(null);
        }
    };
}

export default ToolbarActionsMergeComponent;
