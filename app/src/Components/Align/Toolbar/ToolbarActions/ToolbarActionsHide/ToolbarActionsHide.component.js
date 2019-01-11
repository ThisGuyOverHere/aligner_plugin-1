import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import Hotkeys from "react-hot-keys";

class ToolbarActionsHide extends Component {

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
        //console.log(this.props.selection.source);
        //console.log(this.props.selection.target);
        let disabled = false;
        if (!(this.props.selection.source.count > 0 || this.props.selection.target.count > 0)) {
            disabled = true;
        }
        return <Hotkeys
            keyName="alt+h"
            onKeyDown={this.onHideClick}>
            <button
                disabled={disabled}
                onClick={this.onHideClick}>
                Hide
            </button>
        </Hotkeys>;
    }

    // segments list sorting
    segmentsSelectedSorting = (segmentsSelected) => {
        segmentsSelected.source.list.sort((a, b) => {
            return a - b
        });

        segmentsSelected.target.list.sort((a, b) => {
            return a - b
        });

        return segmentsSelected
    };

    onHideClick = () => {
        // init matches array to send
        let matches = [];
        // sort segments list
        let segmentsSelected = this.segmentsSelectedSorting(this.props.selection);
        // create match and fill source field
        segmentsSelected.source.list.map((item, index) => {
            matches.push({
                source: item,
                target: '',
                to_hide: ''
            })
        });
        // create target or fill existing target field
        segmentsSelected.target.list.map((item, index) => {
            let matched = false;
            matches.map((match, matchIndex) => {
                if(match.source === item){
                    match.target = item;
                    matched = true;
                }
            });
            if(matched){
                matched = !matched
            }else{
                matches.push({
                    source: '',
                    target: item,
                    to_hide: ''
                })
            }
        });
        // analyse amtches and fill to_hide field with right action
        matches.map((item, index) => {
            if (item.source === item.target) {
                item.to_hide = 'both';
            } else if (item.source) {
                item.target = item.source;
                item.to_hide = 'source';
            } else {
                item.source = item.target;
                item.to_hide = 'target';
            }
        });
        // call hide action and collateral actions
        ProjectActions.hideSegments(matches);
        ProjectActions.addSegmentToSelection(-1);
        ProjectActions.onActionHover(null);
    }
}

export default ToolbarActionsHide;
